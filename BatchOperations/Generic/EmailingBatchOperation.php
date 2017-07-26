<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 12/06/14
 * Time: 18:13.
 */

namespace Sygefor\Bundle\CoreBundle\BatchOperations\Generic;

use Doctrine\ORM\EntityManager;
use Sygefor\Bundle\CoreBundle\BatchOperations\AbstractBatchOperation;
use Sygefor\Bundle\CoreBundle\Entity\Email;
use Sygefor\Bundle\CoreBundle\Entity\User;
use Sygefor\Bundle\CoreBundle\HumanReadablePropertyAccessor\HumanReadablePropertyAccessor;
use Sygefor\Bundle\CoreBundle\Entity\AbstractTrainee;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;

class EmailingBatchOperation extends AbstractBatchOperation
{
    /** @var ContainerBuilder $container */
    protected $container;

    protected $targetClass = 'SygeforCoreBundle:AbstractTrainee';

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
        $templateTerm = $this->container->get('sygefor_core.vocabulary_registry')->getVocabularyById('sygefor_trainee.vocabulary_email_template');
        /** @var EntityManager $em */
        $em = $this->em;
        $repo = $em->getRepository(get_class($templateTerm));

        if (!empty($options['inscriptionStatus'])) {
            $repoInscriptionStatus = $em->getRepository('Sygefor\Bundle\CoreBundle\Entity\Term\InscriptionStatus');
            $inscriptionStatus = $repoInscriptionStatus->findById($options['inscriptionStatus']);
            $templates = $repo->findBy(array('inscriptionStatus' => $inscriptionStatus, 'organization' => $this->container->get('security.context')->getToken()->getUser()->getOrganization()));
        } elseif (!empty($options['presenceStatus'])) {
            $repoPresenceStatus = $em->getRepository('Sygefor\Bundle\CoreBundle\Entity\Term\PresenceStatus');
            $presenceStatus = $repoPresenceStatus->findById($options['presenceStatus']);
            $templates = $repo->findBy(array('presenceStatus' => $presenceStatus, 'organization' => $this->container->get('security.context')->getToken()->getUser()->getOrganization()));
        } else {
            //if no presence/inscription status is found, we get all organization templates
            $templates = $repo->findBy(array('organization' => $this->container->get('security.context')->getToken()->getUser()->getOrganization(), 'presenceStatus' => null, 'inscriptionStatus' => null));
        }

        return array('templates' => $templates);
    }

    /**
     * Parses subject and body content according to entity, and sends the mail.
     * WARNING / an $em->clear() is done if there is more than one entity.
     *
     * @param $entities
     * @param $subject
     * @param $cc
     * @param $body
     * @param array $attachments
     * @param bool  $preview
     *
     * @return array
     */
    public function parseAndSendMail($entities, $subject, $cc = array(), $body, $templateAttachments, $attachments = array(), $preview = false, $organization = null)
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
                'cc' => $cc,
                'message' => $this->replaceTokens($body, $entities[0]),
                'templateAttachments' => is_array($templateAttachments) && !empty($templateAttachments) ? array_map(function ($attachment) { return $attachment['name']; }, $templateAttachments) : null,
                'attachments' => $attachments,
            ));
        } else {
            $em = $this->em;
            $message = \Swift_Message::newInstance();

            if (is_int($organization)) {
                $organization = $this->container->get('doctrine')->getRepository('SygeforCoreBundle:Organization')->find($organization);
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
                            }
                        }
                        $publipostTemplates = $em->getRepository('SygeforCoreBundle:Term\PublipostTemplate')->findBy(array('id' => $publipostTemplates));
                        foreach ($publipostTemplates as $publipostTemplate) {
                            // find specific publipost service suffix
                            $entityType = $publipostTemplate->getEntity();
                            $entityType = explode('\\', $entityType);
                            $entityType = $entityType[count($entityType) - 1];
                            $serviceSuffix = strtolower($entityType);

                            // call publipost action and generate pdf
                            $publipostService = $this->container->get('sygefor_core.batch.publipost.'.$serviceSuffix);
                            $publipostIdList = array($entity->getId());
                            $publipostOptions = array('template' => $publipostTemplate->getId());
                            $file = $publipostService->execute($publipostIdList, $publipostOptions);
                            $fileName = $file['fileUrl'];
                            $fileName = $publipostService->getTempDir().$publipostService->toPdf($fileName);

                            // attach pdf to mail
                            if (file_exists($fileName)) {
                                $publipostSwiftAttachment = new \Swift_Attachment(file_get_contents($fileName), $publipostTemplate->getName().'.pdf');
                                $_message->attach($publipostSwiftAttachment);
                            }
                        }
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
                    $_message->setSubject($this->replaceTokens($subject, $entity));
                    $_message->setBody($this->replaceTokens($body, $entity));
                    $last = $this->container->get('mailer')->send($_message);

                    // save email in db
                    $email = new Email();
                    $email->setUserFrom($em->getRepository(User::class)->find($this->container->get('security.context')->getToken()->getUser()->getId()));
                    $email->setEmailFrom($organization->getEmail());
                    foreach ($copies[0] as $key => $copy) {
                        $email->addCc($copy, isset($copies[1][$key]) ? $copies[1][$key] : null);
                    }
                    if (get_parent_class($entity) === 'Sygefor\Bundle\CoreBundle\Entity\AbstractTrainee') {
                        $email->setTrainee($entity);
                    } elseif (get_parent_class($entity) === 'Sygefor\Bundle\CoreBundle\Entity\AbstractTrainer') {
                        $email->setTrainer($entity);
                    } elseif (get_parent_class($entity) === 'Sygefor\Bundle\CoreBundle\Entity\AbstractInscription') {
                        $email->setTrainee($entity->getTrainee());
                        $email->setSession($entity->getSession());
                    }
                    $email->setSubject($_message->getSubject());
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
     * @param $cc
     *
     * @return array
     */
    private function findCCRecipients($entity, $cc)
    {
        // first retrieve trainee
        $trainee = null;
        if (get_parent_class($entity) === 'Sygefor\Bundle\CoreBundle\Entity\AbstractTrainee') {
            $trainee = $entity;
        } elseif (get_parent_class($entity) === 'Sygefor\Bundle\CoreBundle\Entity\AbstractInscription') {
            $trainee = $entity->getTrainee();
        }

        // if trainee not found do nothing
        if (!$trainee) {
            return array(array(), array());
        }

        // use property accessor to get desired values
        $accessor = PropertyAccess::createPropertyAccessor();
        $propertyEmailPathes = array(
            'employer' => 'employer.email',
            'manager' => 'institution.manager.email',
            'trainingCorrespondent' => array('institution.trainingCorrespondents' => 'email'),
        );
        $propertyNamesPathes = array(
            'employer' => 'employer.name',
            'manager' => 'institution.manager.fullName',
            'trainingCorrespondent' => array('institution.trainingCorrespondents' => 'fullName'),
        );

        if (!$trainee->getEmployer()) {
            unset($propertyEmailPathes['employer']);
            unset($propertyNamesPathes['employer']);
        }
        if (!$trainee->getInstitution() || !$trainee->getInstitution()->getManager()) {
            unset($propertyEmailPathes['manager']);
            unset($propertyNamesPathes['manager']);
        }
        if (!$trainee->getInstitution() || $trainee->getInstitution()->getTrainingCorrespondents()->count() === 0) {
            unset($propertyEmailPathes['trainingCorrespondent']);
            unset($propertyNamesPathes['trainingCorrespondent']);
        }

        // get emails and names
        $emails = array();
        $names = array();
        foreach ($cc as $copy => $send) {
            if ($send && isset($propertyEmailPathes[$copy])) {
                if (is_string($propertyEmailPathes[$copy])) {
                    $email = $accessor->getValue($trainee, $propertyEmailPathes[$copy]);
                    $name = $accessor->getValue($trainee, $propertyNamesPathes[$copy]);

                    if ($email) {
                        $emails[] = $email;
                        if ($name) {
                            $names[] = $name;
                        }
                    }
                } elseif (is_array($propertyEmailPathes[$copy])) {
                    $ccEntitiesPath = current(array_keys($propertyEmailPathes[$copy]));
                    $ccEntities = $accessor->getValue($trainee, $ccEntitiesPath);
                    foreach ($ccEntities as $ccEntity) {
                        $email = $accessor->getValue($ccEntity, $propertyEmailPathes[$copy][$ccEntitiesPath]);
                        $name = $accessor->getValue($ccEntity, $propertyNamesPathes[$copy][$ccEntitiesPath]);
                        if ($email) {
                            $emails[] = $email;
                            if ($name) {
                                $names[] = $name;
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
}
