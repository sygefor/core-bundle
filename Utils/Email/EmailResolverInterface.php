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
     * @return string
     */
    public static function getName();

    /**
     * @param $class
     *
     * @return bool
     */
    public static function supports($class);

    /**
     * @param $entity
     *
     * @return string
     */
    public static function resolveName($entity);

    /**
     * @param $entity
     *
     * @return string
     */
    public static function resolveEmail($entity);
}