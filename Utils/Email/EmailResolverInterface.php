<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 8/1/17
 * Time: 4:13 PM.
 */

namespace Sygefor\Bundle\CoreBundle\Utils\Email;

/**
 * Interface EmailResolverInterface.
 */
interface EmailResolverInterface
{
    /**
     * @return mixed
     */
    public static function getName();

    /**
     * @param $entity
     *
     * @return mixed
     */
    public static function supports($entity);

    /**
     * @param $entity
     *
     * @return mixed
     */
    public static function resolve($entity);
}
