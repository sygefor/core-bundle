<?php

namespace Sygefor\Bundle\CoreBundle\Entity\Term;

use Sygefor\Bundle\CoreBundle\Entity\AbstractOrganization;

/**
 * Interface VocabularyInterface.
 */
interface VocabularyInterface
{
    const VOCABULARY_NATIONAL = 0;
    const VOCABULARY_LOCAL = 1;
    const VOCABULARY_MIXED = 2;

    /**
     * @return bool
     */
    public static function getVocabularyStatus();

    /**
     * @return AbstractOrganization|null mixed
     */
    public function getOrganization();

    /**
     * @param AbstractOrganization $organization
     */
    public function setOrganization($organization);

    /**
     * @return mixed
     */
    public function getVocabularyId();

    /**
     * @param string $id
     */
    public function setVocabularyId($id);

    /**
     * @return mixed
     */
    public function getVocabularyName();
}
