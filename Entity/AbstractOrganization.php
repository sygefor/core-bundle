<?php

namespace Sygefor\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Sygefor\Bundle\CoreBundle\Form\Type\AbstractOrganizationType;

/**
 * Organization.
 *
 * IMPORTANT : serialization is handle by YML
 * to prevent rules from CoordinatesTrait being applied to private infos (trainee, trainer)
 *
 * @see Resources/config/serializer/Entity.Organization.yml
 * NO SERIALIZATION INFO IN ANNOTATIONS !!!
 *
 * @ORM\Table(name="organization")
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 */
abstract class AbstractOrganization
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=32)
     */
    protected $code;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="User", mappedBy="organization", cascade={"persist", "merge"})
     * @Serializer\Exclude()
     */
    private $users;

    /**
     * @var bool
     * @ORM\Column(name="trainee_registrable", type="boolean")
     */
    protected $traineeRegistrable = true;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param ArrayCollection $users
     */
    public function setUsers($users)
    {
        $this->users = $users;
    }

    /**
     * @return ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return bool
     */
    public function getTraineeRegistrable()
    {
        return $this->traineeRegistrable;
    }

    /**
     * @param bool $traineeRegistrable
     */
    public function setTraineeRegistrable($traineeRegistrable)
    {
        $this->traineeRegistrable = $traineeRegistrable;
    }

    public static function getFormType()
    {
        return AbstractOrganizationType::class;
    }

    /**
     * @return string
     */
    public static function getType()
    {
        return 'trainer';
    }
}
