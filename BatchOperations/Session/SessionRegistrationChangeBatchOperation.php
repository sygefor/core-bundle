<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 23/06/14
 * Time: 10:13.
 */

namespace Sygefor\Bundle\CoreBundle\BatchOperations\Session;

use Sygefor\Bundle\CoreBundle\BatchOperations\AbstractBatchOperation;
use Sygefor\Bundle\CoreBundle\Entity\AbstractInscription;
use Sygefor\Bundle\CoreBundle\Entity\Session\AbstractSession;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class InscriptionStatusChangeBatchOperation.
 */
class SessionRegistrationChangeBatchOperation extends AbstractBatchOperation
{
    /** @var ContainerBuilder $container */
    private $container;

    /**
     * @var string
     */
    protected $targetClass = 'SygeforCoreBundle:Session\AbstractSession';

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
        $em = $this->container->get('doctrine.orm.entity_manager');
        /* @var AbstractInscription[] $inscriptions */
        $sessions = $this->getObjectList($idList);
        $registration = $options['registration'];
        //changing status
        /** @var AbstractSession $session */
        foreach ($sessions as $session) {
            if ($this->container->get('security.context')->isGranted('EDIT', $session->getTraining())) {
                $session->setRegistration($registration);
            }
        }
        $em->flush();
    }
}
