<?php

/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 24/08/2015
 * Time: 14:34.
 */
namespace Sygefor\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Sygefor\Bundle\CoreBundle\Entity\User\User;

/**
 * Email.
 *
 * @ORM\Table(name="email")
 * @ORM\Entity
 */
class Email
{
    /**
     * @var int id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="Sygefor\Bundle\CoreBundle\Entity\User\User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @Serializer\Groups({"user"})
     */
    protected $userFrom;

    /**
     * @var string
     * @ORM\Column(name="emailFrom", type="string", length=128, nullable=true)
     */
    protected $emailFrom;

    /**
     * @var
     * @ORM\ManyToOne(targetEntity="Sygefor\Bundle\TraineeBundle\Entity\AbstractTrainee")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    protected $trainee;

    /**
     * @var
     * @ORM\ManyToOne(targetEntity="Sygefor\Bundle\TrainerBundle\Entity\AbstractTrainer")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    protected $trainer;

    /**
     * @var
     * @ORM\ManyToOne(targetEntity="Sygefor\Bundle\TrainingBundle\Entity\Session\AbstractSession")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected $session;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $sendAt;

    /**
     * @var string
     * @ORM\Column(name="subject", type="string", length=512, nullable=true)
     */
    protected $subject;

    /**
     * @var array
     * @ORM\Column(name="cc", type="array", nullable=true)
     */
    protected $cc;

    /**
     * @var string
     * @ORM\Column(name="body", type="text", nullable=true)
     */
    protected $body;

    public function __construct()
    {
        $this->cc = array();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUserFrom()
    {
        return $this->userFrom;
    }

    /**
     * @param User $userFrom
     */
    public function setUserFrom($userFrom)
    {
        $this->userFrom = $userFrom;
    }

    /**
     * @return string
     */
    public function getEmailFrom()
    {
        return $this->emailFrom;
    }

    /**
     * @param string $emailFrom
     */
    public function setEmailFrom($emailFrom)
    {
        $this->emailFrom = $emailFrom;
    }

    /**
     * @return mixed
     */
    public function getTrainee()
    {
        return $this->trainee;
    }

    /**
     * @param mixed $trainee
     */
    public function setTrainee($trainee)
    {
        $this->trainee = $trainee;
    }

    /**
     * @return mixed
     */
    public function getTrainer()
    {
        return $this->trainer;
    }

    /**
     * @param mixed $trainer
     */
    public function setTrainer($trainer)
    {
        $this->trainer = $trainer;
    }

    /**
     * @return mixed
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param mixed $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * @return string
     */
    public function getSendAt()
    {
        return $this->sendAt;
    }

    /**
     * @param string $sendAt
     */
    public function setSendAt($sendAt)
    {
        $this->sendAt = $sendAt;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return array
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * @param array $cc
     */
    public function setCc($cc)
    {
        $this->cc = $cc;
    }

    /**
     * @param string $cc
     * @param string $name
     *
     * @return bool
     */
    public function addCc($cc, $name)
    {
        if ( ! isset($this->cc[$cc])) {
            $this->cc[$cc] = $name;

            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }
}
