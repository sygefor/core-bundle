<?php

namespace Sygefor\Bundle\CoreBundle\Entity\Term;

use Doctrine\ORM\Mapping as ORM;

/**
 * GeographicOrigin.
 *
 * @ORM\Table(name="geographic_origin")
 * @ORM\Entity
 * Traduction: Origine Géographique
 */
class GeographicOrigin extends AbstractTerm implements VocabularyInterface
{
    /**
     * This term is required during term replacement.
     *
     * @var bool
     */
    public static $replacementRequired = true;

    /**
     * @return mixed
     */
    public function getVocabularyName()
    {
        return 'Origine Géographique';
    }

    public static function getVocabularyStatus()
    {
        return VocabularyInterface::VOCABULARY_NATIONAL;
    }
}
