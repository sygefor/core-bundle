<?php

namespace Sygefor\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Entity\User as BaseUser;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User.
 *
 * @ORM\Table(name="user")
 * @ORM\Entity()
 */
class User extends BaseUser
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var AbstractOrganization
     * @ORM\ManyToOne(targetEntity="AbstractOrganization", inversedBy="users", cascade={"persist", "merge"})
     * @Assert\NotNull()
     */
    protected $organization;

    /**
     * @var string
     * @ORM\Column(name="access_rights", type="simple_array", nullable=true)
     */
    protected $accessRights;

    /**
     * @var string
     */
    protected $accessRightScope;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->accessRights = array();
        $this->enabled = true;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param AbstractOrganization $organization
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
    }

    /**
     * @return AbstractOrganization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @return mixed
     */
    public function getAccessRights()
    {
        return $this->accessRights;
    }

    /**
     * @param mixed $accessRights
     */
    public function setAccessRights($accessRights)
    {
        $this->accessRights = $accessRights ? $accessRights : array();
    }

    /**
     * @return string
     */
    public function getAccessRightScope()
    {
        return $this->accessRightScope;
    }

    /**
     * @param string $accessRightScope
     */
    public function setAccessRightScope($accessRightScope)
    {
        $this->accessRightScope = $accessRightScope;
    }

    /**
     * @return bool
     */
    public function isAdmin()
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    /**
     * Override default method.
     *
     * @param string $emailCanonical
     *
     * @return $this|\FOS\CoreBundle\Model\UserInterface
     */
    public function setEmailCanonical($emailCanonical)
    {
        return parent::setEmailCanonical(strval(uniqid()).$emailCanonical);
    }
}
