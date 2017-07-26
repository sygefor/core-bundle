<?php

namespace Sygefor\Bundle\CoreBundle\Entity\Term;

use Doctrine\ORM\Mapping as ORM;
use Sygefor\Bundle\CoreBundle\Form\EmailTemplateVocabularyType;

/**
 * Class EmailTemplates.
 *
 * @ORM\Table(name="trainee_email_template")
 * @ORM\Entity
 */
class EmailTemplate extends AbstractTerm implements VocabularyInterface
{
    /**
     * @ORM\Column(name="subject", type="string", length=255, nullable=false)
     *
     * @var string
     */
    private $subject;

    /**
     * @ORM\Column(name="cc", type="array", nullable=true)
     *
     * @var array
     */
    private $cc;

    /**
     * @ORM\Column(name="body", type="text", nullable=false)
     *
     * @var string
     */
    private $body;

    /**
     * @ORM\ManyToOne(targetEntity="InscriptionStatus")
     *
     * @var InscriptionStatus
     */
    protected $inscriptionStatus;

    /**
     * @ORM\ManyToOne(targetEntity="PresenceStatus")
     */
    protected $presenceStatus;

    /**
     * @ORM\ManyToMany(targetEntity="PublipostTemplate")
     * @ORM\JoinTable(name="email_templates__publipost_templates",
     *      joinColumns={@ORM\JoinColumn(name="email_template_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="publipost_template_id", referencedColumnName="id")}
     * )
     *
     * @var PublipostTemplate
     */
    protected $attachmentTemplates;

    /**
     * @param PublipostTemplate $attachmentTemplates
     */
    public function setAttachmentTemplates($attachmentTemplates)
    {
        $this->attachmentTemplates = $attachmentTemplates;
    }

    /**
     * @return PublipostTemplate
     */
    public function getAttachmentTemplates()
    {
        return $this->attachmentTemplates;
    }

    /**
     * @param mixed $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
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
     * @param InscriptionStatus $inscriptionStatus
     */
    public function setInscriptionStatus($inscriptionStatus)
    {
        $this->inscriptionStatus = $inscriptionStatus;
    }

    /**
     * @return InscriptionStatus
     */
    public function getInscriptionStatus()
    {
        return $this->inscriptionStatus;
    }

    /**
     * @param PresenceStatus $presenceStatus
     */
    public function setPresenceStatus($presenceStatus)
    {
        $this->presenceStatus = $presenceStatus;
    }

    /**
     * @return PresenceStatus
     */
    public function getPresenceStatus()
    {
        return $this->presenceStatus;
    }

    /**
     * @return mixed
     */
    public function getVocabularyName()
    {
        return 'Modèles d\'emails stagiaires';
    }

    /**
     * returns the form type name for template edition.
     *
     * @return string
     */
    public static function getFormType()
    {
        return EmailTemplateVocabularyType::class;
    }

    public static function getVocabularyStatus()
    {
        return VocabularyInterface::VOCABULARY_LOCAL;
    }
}
