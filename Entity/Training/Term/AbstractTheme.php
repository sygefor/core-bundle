<?php

namespace Sygefor\Bundle\CoreBundle\Entity\Training\Term;

use Sygefor\Bundle\CoreBundle\Entity\Term\AbstractTerm;
use Sygefor\Bundle\CoreBundle\Entity\Term\VocabularyInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class AbstractTheme.
 *
 * @ORM\Table(name="theme")
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 */
class AbstractTheme extends AbstractTerm implements VocabularyInterface
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
        return 'Thématiques de formation';
    }

    public static function getVocabularyStatus()
    {
        return VocabularyInterface::VOCABULARY_NATIONAL;
    }

    public static function orderBy()
    {
        return 'id';
    }
}
