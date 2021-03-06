<?php

namespace Sygefor\Bundle\CoreBundle\DataFixtures;

use Sygefor\Bundle\CoreBundle\Entity\AbstractOrganization;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Created by PhpStorm.
 * User: Blaise
 * Date: 09/06/2016
 * Time: 12:24.
 */
abstract class AbstractTermLoad extends AbstractDataFixture
{
    public static $class;

    abstract public function getTerms();

    protected $organizations;

    /** @var ObjectManager */
    protected $manager;

    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $manager
     */
    public function doLoad(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->organizations = $manager->getRepository(AbstractOrganization::class)->findAll();

        $this->autoId = 0;
        $metadata = $manager->getClassMetaData($this::$class);
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($this->getTerms() as $term) {
            $class = $this::$class;
            $entity = new $class();
            $entity->setId(++$this->autoId);

            if (!is_array($term)) {
                $term = array('name' => $term);
            }

            foreach ($term as $key => $value) {
                $propertyAccessor->setValue($entity, $key, $value);
                if ($key === 'name' && $value === 'Autre' && method_exists($entity, 'setMachineName')) {
                    $entity->setMachineName('other');
                }
            }
            if (method_exists($entity, 'setPosition')) {
                $entity->setPosition($entity->getMachineName() === 'other' ? 1 : 0);
            }
            $manager->persist($entity);
        }
        $manager->flush();
    }

    public function getOrder()
    {
        return 1;
    }
}
