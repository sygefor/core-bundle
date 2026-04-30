<?php

namespace Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\Vocabulary;

use Sygefor\Bundle\CoreBundle\Entity\Term\VocabularyInterface;
use Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class NationalVocabularyViewAccessRight extends AbstractAccessRight
{
    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Voir les vocabulaires de tous les centres';
    }

    /**
     * Checks if the access right supports the given class.
     *
     * @param string
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        if ($class === VocabularyInterface::class) {
            return true;
        }

        try {
            $refl = new \ReflectionClass($class);

            return $refl->isSubclassOf(VocabularyInterface::class);
        } catch (\ReflectionException $re) {
            return false;
        }
    }

    /**
     * Returns the vote for the given parameters.
     */
    public function isGranted(TokenInterface $token, $object = null, $attribute)
    {
        // shouldn't it be just a check on the attribute ? if the user has the right to view national vocabularies, then he should be able to see all vocabularies, including local ones without organization
        // return $attribute === 'VIEW'; // seems a lot simpler

        if (is_string($token)) {
            return $attribute === 'VIEW';
        } elseif ($object
            && ($object->getVocabularyStatus() === VocabularyInterface::VOCABULARY_NATIONAL // vocabulary is either national
                || ($object->getVocabularyStatus() !== VocabularyInterface::VOCABULARY_NATIONAL && !$object->getOrganization()))) { // or is local without organization
            return $attribute === 'VIEW';
        } else { // or anything else...
            return $attribute === 'VIEW';
        }
    }
}
