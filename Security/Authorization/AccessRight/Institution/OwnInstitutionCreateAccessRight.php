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
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OwnInstitutionCreateAccessRight extends AbstractAccessRight
{
    protected $supportedClass = AbstractInstitution::class;
    protected $supportedOperation = 'CREATE';

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Créer les établissements de son propre centre';
    }

    /**
     * Returns the vote for the given parameters.
     */
    public function isGranted(TokenInterface $token, $object = null, $attribute)
    {
        if ($attribute !== $this->supportedOperation) {
            return false;
        }
        if ($object) {
            return $object->getOrganization()->getId() === $token->getUser()->getOrganization()->getId();
        }

        return true;
    }
}
