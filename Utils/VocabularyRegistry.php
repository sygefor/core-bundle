<?php

namespace Sygefor\Bundle\CoreBundle\Utils;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sygefor\Bundle\CoreBundle\Entity\Term\VocabularyInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Class VocabularyRegistry.
 */
class VocabularyRegistry
{
    /**
     * @var array
     */
    private $vocabularies;

    /**
     * @var array
     */
    private $groups;

    /**
     * @var array
     */
    private $labels;

    public function __construct()
    {
        $this->vocabularies = array();
        $this->groups = array();
        $this->labels = array();
    }

    /**
     * @param VocabularyInterface $vocabulary
     */
    public function addVocabulary(VocabularyInterface $vocabulary, $id, $group = 'Misc', $label = null)
    {
        $vocabulary->setVocabularyId($id);
        $this->vocabularies[$id] = $vocabulary;
        if ($label) {
            $this->labels[$id] = $label;
        }
        if (empty($this->groups[$group])) {
            $this->groups[$group] = array();
        }
        $this->groups[$group][$id] = $vocabulary;
    }

    /**
     * @param string $id
     *
     * @return VocabularyInterface
     */
    public function getVocabularyById($id)
    {
        return isset($this->vocabularies[$id]) ? $this->vocabularies[$id] : null;
    }

    /**
     * @param string $id
     *
     * @return VocabularyInterface
     */
    public function getVocabularyLabel($id)
    {
        return isset($this->labels[$id]) ? $this->labels[$id] : null;
    }

    /**
     * @return array
     */
    public function getVocabularies()
    {
        return $this->vocabularies;
    }

    /**
     * returns known groups.
     *
     * @return array
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Counts and returns the number of usages of term among all entities.
     *
     * @param EntityManager $em
     * @param $vocTerm
     * @param bool $getCount
     * @param int|null $limit
     * @param int $offset
     *
     * @return array|int
     */
    public function getTermUsages(EntityManager $em, $vocTerm, $getCount = true, $limit = null, $offset = 0)
    {
        /* @var ObjectRepository $repo */
        $meta = $em->getMetadataFactory()->getAllMetadata();
        $vocClass = get_class($vocTerm);
        $termId = $vocTerm->getId();

        $usages = array();

        $totalCount = 0;

        /** @var ClassMetadata $m */
        foreach ($meta as $m) {
            $mapps = $m->getAssociationMappings();
            foreach ($mapps as $map) {
                if ($vocClass === $map['targetEntity'] && ($map['isOwningSide'])) {
                    if ($map['type'] === ClassMetadataInfo::MANY_TO_MANY) {
                        if ($getCount) {
                            // Count query for MANY_TO_MANY
                            $qb1 = $em->createQueryBuilder();
                            $qb1->select('COUNT(f.id)')
                                ->from($m->getName(), 'f')
                                ->leftJoin('f.'.$map['fieldName'], 'c')
                                ->where($qb1->expr()->in('c', ':c'))
                                ->setParameter('c', $vocTerm);
                            $count = $qb1->getQuery()->getSingleScalarResult();
                            $totalCount += $count;
                        } else {
                            // Fully query the entities for MANY_TO_MANY, we have to do it in two steps because of the pagination
                            $qb1 = $em->createQueryBuilder();
                            $qb2 = $em->createQueryBuilder();
                            $qb1->select('f.id')
                                ->from($m->getName(), 'f')
                                ->leftJoin('f.'.$map['fieldName'], 'c')
                                ->where($qb1->expr()->in('c', ':c'))
                                ->setParameter('c', $vocTerm);

                            $qb2->select('t')
                                ->from($m->getName(), 't')
                                ->where($qb1->expr()->in('t.id', ':ids'))->setParameter('ids', $qb1->getQuery()->getResult());

                            if ($limit !== null) {
                                $qb2->setFirstResult($offset)->setMaxResults($limit);
                            }

                            $tmpArray = $qb2->getQuery()->getResult();

                            if (count($tmpArray)) {
                                $totalCount += count($tmpArray);
                                $usages[$m->getName()] = array('multiple' => true, 'fieldName' => $map['fieldName'], 'entities' => $tmpArray);
                            }
                        }
                    } else {
                        if ($getCount) {
                            // Count query for single associations
                            $qb = $em->createQueryBuilder()
                                ->select('COUNT(t)')
                                ->from($m->getName(), 't')
                                ->where('t.'.$map['fieldName'].'= :id')->setParameter('id', $termId);
                            $count = $qb->getQuery()->getSingleScalarResult();
                            $totalCount += $count;
                        } else {
                            // Fully query the entities for single associations
                            $qb = $em->createQueryBuilder()
                                ->select('t')
                                ->from($m->getName(), 't')
                                ->where('t.'.$map['fieldName'].'= :id')->setParameter('id', $termId);

                            if ($limit !== null) {
                                $qb->setFirstResult($offset)->setMaxResults($limit);
                            }

                            $tmpArray = $qb->getQuery()->getResult();
                            if (count($tmpArray)) {
                                $totalCount += count($tmpArray);
                                $usages[$m->getName()] = array('multiple' => false, 'fieldName' => $map['fieldName'], 'entities' => $tmpArray);
                            }
                        }
                    }
                }
            }
        }

        if ($getCount) {
            return $totalCount;
        }

        return $usages;
    }

    /**
     * Replaces the a term by another in all its usages.
     *
     * @param EntityManager $em
     * @param $vocTermFrom
     * @param $voctTermTo
     */
    public function replaceTermInUsages(EntityManager $em, $vocTermFrom, $vocTermTo)
    {
        $propAccessor = PropertyAccess::createPropertyAccessor();
        $destinationVocabularyClass = get_class($vocTermTo);
        $batchSize = 20;
        $currentBatch = 0;
        $limit = 100;
        $offset = 0;

        while (true) {
            $usages = $this->getTermUsages($em, $vocTermFrom, false, $limit, $offset);
            $processed = 0;

            foreach ($usages as $class => $classUsage) { // Loop on all classes using the term
                foreach ($classUsage['entities'] as $entitySummary) { // Loop on all entities of the class using the term
                    $processed++;
                    $currentBatch += 1;
                    $doctrineEntity = $em->getRepository($class)->findBy(array('id' => $entitySummary->getId()));
                    $doctrineEntity = $doctrineEntity[0];

                    // value will hold the value of the field using the term, it can be a single value or a collection
                    $value = null;

                    if ($classUsage['multiple'] === true) {
                        $value = $propAccessor->getValue($doctrineEntity, $classUsage['fieldName']);
                    } else {
                        $value = $propAccessor->getValue($doctrineEntity, $classUsage['fieldName']);
                    }

                    if ($value instanceof $destinationVocabularyClass) { // single value field, easy substitution
                        $propAccessor->setValue($doctrineEntity, $classUsage['fieldName'], $vocTermTo);
                    } else { // multiple value field, we have to check if the term is in the collection, and if yes, replace it by the new one
                        $termInCollection = $this->checkTermIsInCollection($vocTermTo, $value);
                        if (is_array($value)) {
                            for ($pos = 0; $pos < count($value); ++$pos) {
                                if (method_exists($value[$pos], 'getId') && ($value[$pos]->getId() === $vocTermFrom->getId())) {
                                    //if destination element is not already present in collection, we can do a replacement
                                    if ($termInCollection) {
                                        array_splice($value, $pos, 1);
                                        break;
                                    } else {
                                        $value[$pos] = $vocTermTo;
                                    }
                                }
                            }
                            $propAccessor->setValue($doctrineEntity, $classUsage['fieldName'], $value);
                        } elseif ($value instanceof \Traversable) {
                            foreach ($value as $key => $val) {
                                if (method_exists($val, 'getId') && ($val->getId() === $vocTermFrom->getId())) {
                                    if ($termInCollection) {
                                        $value->remove($key);
                                        break;
                                    } else {
                                        $value->offsetSet($key, $vocTermTo);
                                    }
                                }
                            }
                            $propAccessor->setValue($doctrineEntity, $classUsage['fieldName'], $value);
                        }
                    }

                    // flush if we've reached the batch size
                    if ($currentBatch >= $batchSize) {
                        $em->flush();
                        $currentBatch = 0;
                    }
                }
            }

            if ($processed == 0) break;
            $offset += $limit;
        }

        // final flush for remaining entities
        $em->flush();
    }

    /**
     * @param $term
     * @param $collection
     *
     * @return bool
     */
    protected function checkTermIsInCollection($term, $collection)
    {
        $isInCollection = false;

        if ($collection instanceof \Traversable) {
            $collection = $collection->toArray();
        }

        foreach ($collection as $key => $val) {
            if (method_exists($val, 'getId') && ($val->getId() === $term->getId())) {
                $isInCollection = true;
            }
        }

        return $isInCollection;
    }
}
