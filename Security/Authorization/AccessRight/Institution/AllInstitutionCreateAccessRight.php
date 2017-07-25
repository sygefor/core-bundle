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

class AllInstitutionCreateAccessRight extends AbstractAccessRight
{
    protected $supportedClass = AbstractInstitution::class;
    protected $supportedOperation = 'CREATE';

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Créer les établissements de tous les centres';
    }
}
