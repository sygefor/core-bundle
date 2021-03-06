<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 28/04/14
 * Time: 10:41.
 */

namespace Sygefor\Bundle\CoreBundle\BatchOperations\Inscription;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Sygefor\Bundle\CoreBundle\BatchOperations\AbstractBatchOperation;
use Sygefor\Bundle\CoreBundle\Entity\AbstractInscription;
use Sygefor\Bundle\CoreBundle\Entity\Term\InscriptionStatus;
use Sygefor\Bundle\CoreBundle\Entity\Term\PresenceStatus;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MailingBatchOperation.
 */
class InscriptionStatusChangeBatchOperation extends AbstractBatchOperation implements ContainerAwareInterface
{
    /** @var ContainerBuilder $container */
    private $container;

    /**
     * @var string
     */
    protected $targetClass = 'SygeforCoreBundle:AbstractInscription';

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
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
        $inscriptions = $this->getObjectList($idList);
        //sending email

        $em = $this->container->get('doctrine.orm.entity_manager');
        $repoInscriptionStatus = $em->getRepository(InscriptionStatus::class);
        $repoPresenceStatus = $em->getRepository(PresenceStatus::class);

        $inscriptionStatus = (empty($options['inscriptionStatus'])) ? null : $repoInscriptionStatus->find($options['inscriptionStatus']);
        $presenceStatus = (empty($options['presenceStatus'])) ? null : $repoPresenceStatus->find($options['presenceStatus']);

        //changing status
        $arrayInscriptionsGranted = array();
        /** @var AbstractInscription $inscription */
        foreach ($inscriptions as $inscription) {
            if ($this->container->get('security.context')->isGranted('EDIT', $inscription)) {
                //setting new inscription status
                if ($inscriptionStatus) {
                    $inscription->setInscriptionStatus($inscriptionStatus);
                } elseif ($presenceStatus) {
                    $inscription->setPresenceStatus($presenceStatus);
                }
                $arrayInscriptionsGranted[] = $inscription;
            }
        }
        $em->flush();

        // if asked, a mail sent to user
        if (isset($options['sendMail']) && ($options['sendMail'] === true) && (count($arrayInscriptionsGranted) > 0)) {
            foreach ($arrayInscriptionsGranted as $inscription) {
                // sending with e-mail service
                if (!isset($options['cc'])) {
                    $options['cc'] = array();
                }
                $this->container->get('sygefor_core.batch.email')->parseAndSendMail(
                    $inscription,
                    $options['subject'],
                    $options['cc'],
                    isset($options['additionalCC']) ? $options['additionalCC'] : null,
                    $options['message'],
                    isset($options['attachmentTemplates']) ? $options['attachmentTemplates'] : array(),
                    isset($options['attachment']) ? $options['attachment'] : array(),
                    (isset($options['preview'])) ? $options['preview'] : false,
                    null
                );
            }
        }

        $data = array();
        foreach ($arrayInscriptionsGranted as $inscription) {
            $data[] = [
                'id' => $inscription->getId(),
                'inscriptionStatus' => $inscription->getInscriptionStatus(),
                'presenceStatus' => $inscription->getPresenceStatus(),
            ];
        }

        return $data;
    }

    /**
     * @param $options
     *
     * @return array
     */
    public function getModalConfig($options = array())
    {
        $userOrg = $this->container->get('security.context')->getToken()->getUser()->getOrganization();
        $templateTerm = $this->container->get('sygefor_core.vocabulary_registry')->getVocabularyById('sygefor_core.vocabulary_email_template');
        $attachmentTerm = $this->container->get('sygefor_core.vocabulary_registry')->getVocabularyById('sygefor_core.vocabulary_publipost_template');

        /** @var EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        /** @var EntityRepository $repo */
        $repo = $em->getRepository(get_class($templateTerm));
        $attRepo = $em->getRepository(get_class($attachmentTerm));

        if (!empty($options['inscriptionStatus'])) {
            $repoInscriptionStatus = $em->getRepository(InscriptionStatus::class);
            $inscriptionStatus = $repoInscriptionStatus->findById($options['inscriptionStatus']);
            $findCriteria = array('inscriptionStatus' => $inscriptionStatus);

            if ($userOrg) {
                $findCriteria['organization'] = $userOrg;
            }

            $templates = $repo->findBy($findCriteria);
        } elseif (!empty($options['presenceStatus'])) {
            $repoInscriptionStatus = $em->getRepository(PresenceStatus::class);
            $presenceStatus = $repoInscriptionStatus->findById($options['presenceStatus']);
            $findCriteria = array('presenceStatus' => $presenceStatus);

            if ($userOrg) {
                $findCriteria['organization'] = $userOrg;
            }

            $templates = $repo->findBy($findCriteria);
        } else {
            $templates = $repo->findBy(array('inscriptionStatus' => null, 'presenceStatus' => null));
        }

        $attTemplates = $attRepo->findBy(array('organization' => $userOrg ? $userOrg : ''));

        return array(
            'ccResolvers' => $this->container->get('sygefor_core.registry.email_cc_resolver')->getSupportedResolvers($options['targetClass']),
            'templates' => $templates,
            'attachmentTemplates' => $attTemplates,
        );
    }
}
