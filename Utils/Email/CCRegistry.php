<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 8/1/17
 * Time: 4:11 PM.
 */

namespace Sygefor\Bundle\CoreBundle\Utils\Email;

/**
 * Class CCRegistry.
 */
class CCRegistry
{
    /**
     * @var array
     */
    private $resolvers;

    public function __construct($resolvers)
    {
        $this->resolvers = $resolvers;
    }

    /**
     * @return array
     */
    public function getCC()
    {
        $cc = array();
        /** @var EmailResolverInterface $resolver */
        foreach ($this->resolvers as $resolver) {
            $cc[] = $resolver::getName();
        }

        return $cc;
    }

    /**
     * @param $cc
     *
     * @return mixed
     */
    public function getName($cc)
    {
        /** @var EmailResolverInterface $resolver */
        foreach ($this->resolvers as $resolver) {
            if ($cc === $resolver) {
                return $resolver::getName();
            }
        }

        return null;
    }

    /**
     * @param $entity
     *
     * @return mixed
     */
    public function resolve($entity)
    {
        /** @var EmailResolverInterface $resolver */
        foreach ($this->resolvers as $resolver) {
            if ($resolver::supports($entity)) {
                return $resolver::resolve($entity);
            }
        }

        return null;
    }
}
