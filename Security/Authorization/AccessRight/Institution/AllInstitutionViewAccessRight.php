<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 20/03/14
 * Time: 16:46.
 */

namespace Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\Institution;

use Sygefor\Bundle\CoreBundle\Entity\AbstractInstitution;
use Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\AbstractAccessRight;

class AllInstitutionViewAccessRight extends AbstractAccessRight
{
    protected $supportedClass = AbstractInstitution::class;
    protected $supportedOperation = 'VIEW';

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Voir les établissements de tous les centres';
    }
}
