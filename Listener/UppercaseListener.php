<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 12/19/16
 * Time: 9:59 AM
 */

namespace Sygefor\Bundle\CoreBundle\Listener;


use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

/**
 * Class UppercaseListener
 * @package Sygefor\Bundle\CoreBundle\Listener
 */
class UppercaseListener implements EventSubscriber
{
    /**
     * Returns hash of events, that this listener is bound to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     */
    public function preProcess(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        if ($this->isAPerson($entity)) {
            $entity->setLastName($this->upperString($entity->getLastName()));
            $entity->setMaidenName($this->upperString($entity->getMaidenName()));
        }
        if ($this->hasAddress($entity)) {
            $entity->setAddress($this->upperString($entity->getAddress()));
            $entity->setCity($this->upperString($entity->getCity()));
        }
        if ($this->hasAName($entity)) {
            $entity->setName($this->upperFirstLetterName($entity->getName()));
        }
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->preProcess($eventArgs);
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     */
    public function preUpdate(LifecycleEventArgs $eventArgs)
    {
        $this->preProcess($eventArgs);
    }

    /**
     * @param $entity
     * @return bool
     */
    protected function isAPerson($entity)
    {
        return method_exists($entity, 'getLastName') && method_exists($entity, 'setLastName') &&
                method_exists($entity, 'getMaidenName') && method_exists($entity, 'setMaidenName');
    }

    /**
     * @param $entity
     * @return bool
     */
    protected function hasAddress($entity)
    {
        return method_exists($entity, 'getAddress') && method_exists($entity, 'setAddress') &&
                method_exists($entity, 'getCity') && method_exists($entity, 'setCity');
    }

    /**
     * @param $entity
     * @return bool
     */
    protected function hasAName($entity)
    {
        return method_exists($entity, 'getName') && method_exists($entity, 'setName');
    }

    /**
     * @param $string
     *
     * @return string
     */
    protected function upperString($string)
    {
        return mb_strtoupper($string, 'UTF-8');
    }


    /**
     * @param $string
     *
     * @return string
     */
    protected function upperFirstLetterName($string)
    {
        $fc = mb_strtoupper(mb_substr($string, 0, 1), 'UTF-8');
        return $fc.mb_substr($string, 1);
    }
}