<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 12/06/14
 * Time: 18:13.
 */

namespace Sygefor\Bundle\CoreBundle\BatchOperations\Generic;

use Html2Text\Html2Text;
use Doctrine\ORM\EntityManager;
use Sygefor\Bundle\CoreBundle\BatchOperations\AbstractBatchOperation;
use Sygefor\Bundle\CoreBundle\BatchOperations\AttachEmailPublipostAttachment;
use Sygefor\Bundle\CoreBundle\Entity\AbstractInscription;
use Sygefor\Bundle\CoreBundle\Entity\AbstractOrganization;
use Sygefor\Bundle\CoreBundle\Entity\AbstractTrainer;
use Sygefor\Bundle\CoreBundle\Entity\Email;
use Sygefor\Bundle\CoreBundle\Entity\Term\InscriptionStatus;
use Sygefor\Bundle\CoreBundle\Entity\Term\PresenceStatus;
use Sygefor\Bundle\CoreBundle\Entity\Term\PublipostTemplate;
use Sygefor\Bundle\CoreBundle\Entity\User;
use Sygefor\Bundle\CoreBundle\Utils\HumanReadable\HumanReadablePropertyAccessor;
use Sygefor\Bundle\CoreBundle\Entity\AbstractTrainee;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class EmailingBatchOperation extends AbstractBatchOperation
{
    use AttachEmailPublipostAttachment;

    /** @var ContainerBuilder $container */
    protected $container;

    protected $targetClass = AbstractTrainee::class;

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $idList
     * @param array $options
     *
     * @return mixed
     */
    public function execute(array $idList = array(), array $options = array())
    {
        //setting alternate targetclass if provided in options
        if (isset($options['targetClass'])) {
            $this->setTargetClass($options['targetClass']);
        }

        $targetEntities = $this->getObjectList($idList);

        if (isset($options['preview']) && $options['preview']) {
            return $this->parseAndSendMail(
                $targetEntities[0],
                isset($options['subject']) ? $options['subject'] : '',
                isset($options['cc']) ? $options['cc'] : array(),
                isset($options['additionalCC']) ? $options['additionalCC'] : array(),
                isset($options['message']) ? $options['message'] : '',
                isset($options['templateAttachments']) ? $options['templateAttachments'] : null,
                null,
                $preview = true,
                isset($options['organization']) ? $options['organization'] : null
            );
        }

        // check if user has access
        // check trainee proxy for inscription checkout
        if (isset($options['typeUser']) && get_parent_class($options['typeUser']) !== AbstractTrainee::class) {
            foreach ($targetEntities as $key => $user) {
                if (!$this->container->get('security.context')->isGranted('VIEW', $user)) {
                    unset($targetEntities[$key]);
                }
            }
        }

        return $this->parseAndSendMail(
            $targetEntities,
            isset($options['subject']) ? $options['subject'] : '',
            isset($options['cc']) ? $options['cc'] : array(),
            isset($options['additionalCC']) ? $options['additionalCC'] : array(),
            isset($options['message']) ? $options['message'] : '',
            isset($options['templateAttachments']) ? $options['templateAttachments'] : null,
            isset($options['attachment']) ? $options['attachment'] : null,
            false,
            isset($options['organization']) ? $options['organization'] : null
        );

        return new Response('', 204);
    }

    /**
     * @return array configuration element for front-end modal window
     */
    public function getModalConfig($options = array())
    {
        $templateTerm = $this->container->get('sygefor_core.vocabulary_registry')->getVocabularyById('sygefor_core.vocabulary_email_template');
        /** @var EntityManager $em */
        $em = $this->em;
        $repo = $em->getRepository(get_class($templateTerm));

        if (!empty($options['inscriptionStatus'])) {
            $repoInscriptionStatus = $em->getRepository(InscriptionStatus::class);
            $inscriptionStatus = $repoInscriptionStatus->findById($options['inscriptionStatus']);
            $templates = $repo->findBy(array('inscriptionStatus' => $inscriptionStatus, 'organization' => $this->container->get('security.context')->getToken()->getUser()->getOrganization()));
        } elseif (!empty($options['presenceStatus'])) {
            $repoPresenceStatus = $em->getRepository(PresenceStatus::class);
            $presenceStatus = $repoPresenceStatus->findById($options['presenceStatus']);
            $templates = $repo->findBy(array('presenceStatus' => $presenceStatus, 'organization' => $this->container->get('security.context')->getToken()->getUser()->getOrganization()));
        } else {
            //if no presence/inscription status is found, we get all organization templates
            $templates = $repo->findBy(array('organization' => $this->container->get('security.context')->getToken()->getUser()->getOrganization(), 'presenceStatus' => null, 'inscriptionStatus' => null));
        }

        return array(
            'ccResolvers' => $this->container->get('sygefor_core.registry.email_cc_resolver')->getSupportedResolvers($options['targetClass']),
            'templates' => $templates,
        );
    }

    /**
     * Parses subject and body content according to entity, and sends the mail.
     * WARNING / an $em->clear() is done if there is more than one entity.
     *
     * @param $entities
     * @param $subject
     * @param $cc
     * @param $additionalCC
     * @param $body
     * @param array $attachments
     * @param bool  $preview
     *
     * @return array
     */
    public function parseAndSendMail($entities, $subject, $cc = array(), $additionalCC = null, $body, $templateAttachments = array(), $attachments = array(), $preview = false, $organization = null)
    {
        $doClear = true;
        if (!is_array($entities)) {
            $entities = array($entities);
            $doClear = false;
        }

        if (empty($entities)) {
            return;
        }

        if ($preview) {
            return array('email' => array(
                'subject' => $this->replaceTokens($subject, $entities[0]),
                'cc' => array_merge($cc, $this->additionalCCToArray($additionalCC)),
                'message' => $this->replaceTokens($body, $entities[0]),
                'templateAttachments' => is_array($templateAttachments) && !empty($templateAttachments) ? array_map(function ($attachment) { return $attachment['name']; }, $templateAttachments) : null,
                'attachments' => $attachments,
            ));
        } else {
            $last = 0;
            $em = $this->em;
            $message = \Swift_Message::newInstance();

            if (is_int($organization)) {
                $organization = $this->container->get('doctrine')->getRepository(AbstractOrganization::class)->find($organization);
            } else {
                $organization = $this->container->get('security.context')->getToken()->getUser()->getOrganization();
            }

            $message->setFrom($this->container->getParameter('mailer_from'), $organization->getName());
            $message->setReplyTo($organization->getEmail());

            // attachements
            if (!empty($attachments)) {
                if (!is_array($attachments)) {
                    $attachments = array($attachments);
                }
                foreach ($attachments as $attachment) {
                    if (!$attachment instanceof \Swift_Attachment) {
                        $message->attach(new \Swift_Attachment(file_get_contents($attachment), (method_exists($attachment, 'getClientOriginalName')) ? $attachment->getClientOriginalName() : $attachment->getFileName()));
                    } else {
                        $message->attach($attachment);
                    }
                }
            }

            // foreach entity
            $i = 0;
            if ($doClear) {
                $em->clear();
            }
            foreach ($entities as $entity) {
                try {
                    $_message = clone $message;
                    // load publipost templates
                    $publipostTemplates = array();
                    if ($templateAttachments) {
                        foreach ($templateAttachments as $templateAttachment) {
                            if (is_int($templateAttachment)) {
                                $publipostTemplates[] = $templateAttachment;
                            } elseif (is_array($templateAttachment) && isset($templateAttachment['id'])) {
                                $publipostTemplates[] = $templateAttachment['id'];
                            } elseif ($templateAttachment && $templateAttachment instanceof PublipostTemplate) {
                                $publipostTemplates[] = $templateAttachment->getId();
                            }
                        }
                        $publipostTemplates = $em->getRepository(PublipostTemplate::class)->findBy(array('id' => $publipostTemplates));
                        $this->attachPublipostAttachment($_message, $publipostTemplates, array($entity->getId()));
                    }

                    // reload entity because of em clear
                    $entity = $em->getRepository(get_class($entity))->find($entity->getId());
                    $copies = $this->findCCRecipients($entity, $cc);

                    $hrpa = $this->container->get('sygefor_core.human_readable_property_accessor_factory')->getAccessor($entity);
                    $email = $hrpa->email;
                    $_message->setTo($email);
                    $_message->setCc(array());
                    foreach ($copies[0] as $key => $copy) {
                        $_message->addCc($copy, isset($copies[1][$key]) ? $copies[1][$key] : null);
                    }
                    foreach ($this->additionalCCToArray($additionalCC) as $email => $send) {
                        if ($send && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $_message->addCc($email);
                        }
                    }
                    $subject = $this->replaceTokens($subject, $entity);
                    $_message->setSubject('=?UTF-8?B?'.base64_encode($subject).'?=');
                    $_message->setBody($this->replaceTokens($body, $entity));
                    $_message->addPart(Html2Text::convert($_message->getBody()), 'text/plain');
                    $last = $this->container->get('mailer')->send($_message);

                    // save email in db
                    $email = new Email();
                    $email->setUserFrom($em->getRepository(User::class)->find($this->container->get('security.context')->getToken()->getUser()->getId()));
                    $email->setEmailFrom($organization->getEmail());
                    foreach ($copies[0] as $key => $copy) {
                        $email->addCc($copy, isset($copies[1][$key]) ? $copies[1][$key] : null);
                    }
                    if (get_parent_class($entity) === AbstractTrainee::class) {
                        $email->setTrainee($entity);
                    } elseif (get_parent_class($entity) === AbstractTrainer::class) {
                        $email->setTrainer($entity);
                    } elseif (get_parent_class($entity) === AbstractInscription::class) {
                        $email->setTrainee($entity->getTrainee());
                        $email->setSession($entity->getSession());
                    }
                    $email->setSubject($subject);
                    $email->setBody($_message->getBody());
                    $email->setSendAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                    $em->persist($email);
                    if (++$i % 500 === 0) {
                        $em->flush();
                        $em->clear();
                    }
                } catch (\Swift_RfcComplianceException $e) {
                    // continue
                }
            }
            $em->flush();
            if ($doClear) {
                $em->clear();
            }

            return $last;
        }
    }

    /**
     * @param $content
     * @param $entity
     *
     * @return string
     */
    protected function replaceTokens($content, $entity)
    {
        /** @var HumanReadablePropertyAccessor $HRPA */
        $HRPA = $this->container->get('sygefor_core.human_readable_property_accessor_factory')->getAccessor($entity);

        $newContent = preg_replace_callback('/\[(.*?)\]/',
            function ($matches) use ($HRPA) {
                $property = $matches[1];

                return $HRPA->$property;
            },
            $content);

        return $newContent;
    }

    /**
     * Get recipients email and name.
     *
     * @param $entity
     * @param $ccResolvers
     *
     * @return array
     */
    private function findCCRecipients($entity, $ccResolvers)
    {
        $emails = array();
        $names = array();
        $ccResolverRegistry = $this->container->get('sygefor_core.registry.email_cc_resolver');
        // guess cc emails and names
        foreach ($ccResolvers as $resolver => $send) {
            if ($send) {
                $name = $ccResolverRegistry->resolveName($resolver, $entity);
                $email = $ccResolverRegistry->resolveEmail($resolver, $entity);
                if ($email) {
                    if (is_string($email)) {
                        $emails[] = $email;
                    } elseif (is_array($email)) {
                        foreach ($email as $cc) {
                            $emails[] = $cc;
                        }
                    }
                    if ($name) {
                        if (is_string($name)) {
                            $names[] = $name;
                        } elseif (is_array($name)) {
                            foreach ($name as $cc) {
                                $names[] = $cc;
                            }
                        }
                    }
                }
            }
        }

        // do not send to bad fullName
        if (count($names) !== count($emails)) {
            $names = array();
        }

        return array($emails, $names);
    }

    /**
     * @param $additionalCC
     *
     * @return array
     */
    protected function additionalCCToArray($additionalCC)
    {
        if (is_array($additionalCC)) {
            $additionalCC = implode(';', $additionalCC);
        }

        $additionalCC = str_replace(array(' ', ','), ';', $additionalCC);
        $ccParts = explode(';', $additionalCC);
        $ccParts = array_unique($ccParts);
        $ccParts = array_filter($ccParts, function ($cc) {
            return !empty($cc);
        });

        $cc = array();
        foreach ($ccParts as $email) {
            $cc[$email] = true;
        }

        return $cc;
    }
}
