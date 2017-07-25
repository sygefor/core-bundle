<?php

/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 5/24/16
 * Time: 10:11 AM.
 */

namespace Sygefor\Bundle\CoreBundle\Entity\Term;

use Doctrine\ORM\Mapping as ORM;

/**
 * Type d'institution.
 *
 * @ORM\Table(name="institution_type")
 * @ORM\Entity
 */
class InstitutionType extends AbstractTerm implements VocabularyInterface
{
    /**
     * @return mixed
     */
    public function getVocabularyName()
    {
        return "Type d'établissement";
    }

    public static function getVocabularyStatus()
    {
        return VocabularyInterface::VOCABULARY_NATIONAL;
    }
}
