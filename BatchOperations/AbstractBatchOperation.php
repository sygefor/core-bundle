<?php

namespace Sygefor\Bundle\CoreBundle\BatchOperations;

use Doctrine\ORM\EntityManager;

/**
 * Class AbstractBatchOperation.
 */
abstract class AbstractBatchOperation implements BatchOperationInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    protected $targetClass;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @param $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $class
     */
    public function setTargetClass($class)
    {
        $this->targetClass = $class;
    }

    /**
     * @return string
     */
    public function getTargetClass()
    {
        return $this->targetClass;
    }

    /**
     * @var string
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * the label for operation (will be displayed in available operations list).
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param EntityManager $em
     */
    public function setEm(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Return entity array with id list.
     *
     * @param $idList
     *
     * @return array
     */
    protected function getObjectList($idList)
    {
        // $entities = $this->em->getRepository($this->targetClass)->findBy(array('id' => $idList));
        // $this->reorderByKeys($entities, $idList);
        // return $entities;

        // Let's try a sorting that doesn't search for things n times
        // First we index result by their ids instead of arbitrary values
        $qb = $this->em->getRepository($this->targetClass)->createQueryBuilder('e', 'e.id');
        $qb->where('e.id in (:idList)')
            ->setParameter('idList', $idList);
        $entities = $qb->getQuery()->execute();
        
        // then we order the result so it follows the idList order given
        $orderedEntities = [];
        foreach ($idList as $crtOrderedId) {
            $crtEnt = $entities[$crtOrderedId];
            if ($crtEnt) {
                $orderedEntities[] = $crtEnt;
            }
        }

        // The result is 5x times faster for 7000 entities and should not increase exponentially as the previous one did
        return $orderedEntities;
    }

    /**
     * @return array modal window modal config options
     */
    public function getModalConfig($options = array())
    {
        return array();
    }

    /**
     * Re-order a list by keys.
     */
    protected function reorderByKeys(&$items, $keys)
    {
        usort($items, function ($a, $b) use ($keys) {
            $position_a = array_search($a->getId(), $keys);
            $position_b = array_search($b->getId(), $keys);

            return  $position_a < $position_b ? -1 : 1;
        });
    }
}
