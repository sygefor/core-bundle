<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 07/07/14
 * Time: 14:07.
 */

namespace Sygefor\Bundle\CoreBundle\Entity\Term;

use Doctrine\ORM\Mapping as ORM;
use Sygefor\Bundle\CoreBundle\Entity\UploadableTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Sygefor\Bundle\CoreBundle\Form\Type\PublipostTemplateVocabularyType;

/**
 * Class PublipostTemplates.
 *
 * @ORM\Table(name="publipost_template")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class PublipostTemplate extends AbstractTerm implements VocabularyInterface
{
    use UploadableTrait;

    /**
     * @ORM\Column(name="entity", type="text", nullable=false)
     * @Assert\NotNull()
     *
     * @var string
     */
    protected $entity;

    /**
     * @param string $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return int file max size
     */
    public static function getMaxFileSize()
    {
        return 5242880;
    }

    /**
     * @return array
     */
    public static function getAllowedMimeTypes()
    {
        return array(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // docx
            'application/vnd.oasis.opendocument.text', // odt
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
            'application/vnd.oasis.opendocument.spreadsheet', // ods
        );
    }

    /**
     * @return array
     */
    public static function getNotAllowedMimeTypeMessage()
    {
        return 'Vous devez fournir un fichier docx, odt, xlsx ou ods.';
    }

    /**
     * @return mixed
     */
    public function getVocabularyName()
    {
        return 'Modèles de publipostage';
    }

    /**
     * returns the form type name for template edition.
     *
     * @return string
     */
    public static function getFormType()
    {
        return PublipostTemplateVocabularyType::class;
    }

    public static function getVocabularyStatus()
    {
        return VocabularyInterface::VOCABULARY_LOCAL;
    }

    /**
     * @return string
     */
    protected function getTemplatesRootDir()
    {
        // le chemin absolu du répertoire où les documents uploadés doivent être sauvegardés
        return __DIR__.'/../../../../../var/Publipost';
    }
}
