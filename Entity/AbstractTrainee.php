<?php

namespace Sygefor\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use Sygefor\Bundle\ApiBundle\Form\Type\ProfileType;
use Sygefor\Bundle\ApiBundle\Form\Type\RegistrationType;
use Sygefor\Bundle\CoreBundle\Form\Type\AbstractTraineeType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\SerializedAccessRights;

/**
 * Trainee.
 *
 * @ORM\Table(name="trainee", uniqueConstraints={@ORM\UniqueConstraint(name="emailUnique", columns={"email"})}))
 * @ORM\Entity(repositoryClass="Sygefor\Bundle\ApiBundle\Repository\AccountRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields={"email"}, message="Cette adresse email est déjà utilisée.")
 */
abstract class AbstractTrainee implements SerializedAccessRights
{
    use ORMBehaviors\Timestampable\Timestampable;
    use PersonTrait;

    /**
     * @var int id
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var AbstractOrganization
     * @ORM\ManyToOne(targetEntity="AbstractOrganization")
     * @Assert\NotNull(message="Vous devez renseigner un centre de rattachement.")
     * @Serializer\Groups({"trainee", "session", "api.profile", "api.token"})})
     */
    protected $organization;

    /**
     * @var AbstractInscription
     * @ORM\OneToMany(targetEntity="AbstractInscription", mappedBy="trainee", cascade={"remove"})
     * @Serializer\Groups({"trainee"})
     */
    protected $inscriptions;

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->inscriptions = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getFullName();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param ArrayCollection
     */
    public function setInscriptions($inscriptions)
    {
        $this->inscriptions = $inscriptions;
    }

    /**
     * @return ArrayCollection
     */
    public function getInscriptions()
    {
        return $this->inscriptions;
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
    public static function getFormType()
    {
        return AbstractTraineeType::class;
    }

    /**
     * @return mixed
     */
    public static function getProfileFormType()
    {
        return ProfileType::class;
    }

    /**
     * @return mixed
     */
    public static function getRegistrationFormType()
    {
        return RegistrationType::class;
    }

    /**
     * loadValidatorMetadata.
     *
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        // PersonTrait
        $metadata->addPropertyConstraint('title', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner une civilité.',
        )));
        $metadata->addPropertyConstraint('lastName', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner un nom de famille.',
        )));
        $metadata->addPropertyConstraint('firstName', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner un prénom.',
        )));
    }

    /**
     * @return string
     */
    public static function getType()
    {
        return 'trainee';
    }
}
