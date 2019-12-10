<?php

namespace Sygefor\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\Common\Collections\ArrayCollection;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use Sygefor\Bundle\CoreBundle\Form\Type\AbstractTrainingType;
use Symfony\Component\Validator\Constraints as Assert;
use Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\SerializedAccessRights;

/**
 * @ORM\Entity
 * @ORM\Table(name="training", uniqueConstraints={@ORM\UniqueConstraint(name="organization_number", columns={"number", "organization_id"})})
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({})
 * Traduction: Formation
 */
abstract class AbstractTraining implements SerializedAccessRights
{
    use ORMBehaviors\Timestampable\Timestampable;
    use MaterialTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"Default", "api"})
     */
    private $id;

    /**
     * @var AbstractOrganization
     * @ORM\ManyToOne(targetEntity="AbstractOrganization")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotBlank()
     * @Serializer\Groups({"Default", "training", "api"})
     */
    protected $organization;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AbstractSession", mappedBy="training", cascade={"persist", "remove"})
     * @Serializer\Groups({"training", "api.training"})
     */
    protected $sessions;

    /**
     * @ORM\Column(name="number", type="integer")
     * @Serializer\Groups({"Default", "api"})
     */
    protected $number;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank(message="Vous devez renseigner un intitulé.")
     *
     * @var string
     * @Serializer\Groups({"Default", "api"})
     */
    protected $name;

    /**
     * @ORM\Column(name="firstSessionPeriodSemester", type="integer")
     * @Assert\NotNull
     *
     * @var int
     * @Serializer\Groups({"training", "api"})
     */
    protected $firstSessionPeriodSemester = 1;

    /**
     * @ORM\Column(name="firstSessionPeriodYear", type="integer")
     * @Assert\NotNull
     *
     * @var int
     * @Serializer\Groups({"training", "api"})
     */
    protected $firstSessionPeriodYear;

    /**
     * @ORM\Column(name="comments", type="text", nullable=true)
     *
     * @var string
     * @Serializer\Groups({"training"})
     */
    protected $comments;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->firstSessionPeriodYear = (new \DateTime())->format('Y');
        $this->firstSessionPeriodSemester = ((new \DateTime())->format('m') > 6 ? 2 : 1);
        $this->sessions = new ArrayCollection();
        $this->materials = new ArrayCollection();
    }

    /**
     * cloning magic function.
     */
    public function __clone()
    {
        $this->id = null;
        $this->setCreatedAt(new \DateTime());

        //sessions are not copied.
        $this->materials = new ArrayCollection();
        $this->sessions = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @return mixed
     */
    public static function getFormType()
    {
        return AbstractTrainingType::class;
    }

    /**
     * @param $addMethod
     * @param ArrayCollection $arrayCollection
     */
    public function duplicateArrayCollection($addMethod, $arrayCollection)
    {
        foreach ($arrayCollection as $item) {
            if (method_exists($this, $addMethod)) {
                $this->$addMethod($item);
            }
        }
    }

    /**
     * Copy all properties from a training except id and number.
     *
     * @param AbstractTraining $originalTraining
     */
    public function copyProperties($originalTraining)
    {
        foreach (array_keys(get_object_vars($this)) as $key) {
            if ($key !== 'id' && $key !== 'number' && $key !== 'sessions' && $key !== 'session') {
                if (isset($originalTraining->$key)) {
                    $this->$key = $originalTraining->$key;
                }
            }
        }
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return AbstractOrganization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param AbstractOrganization $organization
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param ArrayCollection $sessions
     */
    public function setSessions($sessions)
    {
        $this->sessions = $sessions;
    }

    /**
     * @param AbstractSession $session
     */
    public function addSession($session)
    {
        $this->sessions->add($session);
    }

    /**
     * @param AbstractSession $session
     */
    public function removeSession($session)
    {
        $this->sessions->removeElement($session);
    }

    /**
     * @return ArrayCollection
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * @param mixed $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param string $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    /**
     * @return int
     */
    public function getFirstSessionPeriodSemester()
    {
        return $this->firstSessionPeriodSemester;
    }

    /**
     * @param int $firstSessionPeriodSemester
     */
    public function setFirstSessionPeriodSemester($firstSessionPeriodSemester)
    {
        $this->firstSessionPeriodSemester = $firstSessionPeriodSemester;
    }

    /**
     * @return int
     */
    public function getFirstSessionPeriodYear()
    {
        return $this->firstSessionPeriodYear;
    }

    /**
     * @param int $firstSessionPeriodYear
     */
    public function setFirstSessionPeriodYear($firstSessionPeriodYear)
    {
        $this->firstSessionPeriodYear = $firstSessionPeriodYear;
    }

    /**
     * Used for duplicate training choose type form.
     *
     * @return string
     */
    public function getDuplicatedType()
    {
        return $this->getType();
    }

    /**
     * Used for duplicate training choose type form.
     */
    public function setDuplicatedType($type)
    {
    }

    /**
     * @return string
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"Default", "api"})
     */
    public static function getTypeLabel()
    {
        return 'Formation';
    }

    /**
     * @return string
     *                Serializer : via listener to include in all cases
     */
    public static function getType()
    {
        return 'training';
    }
}
