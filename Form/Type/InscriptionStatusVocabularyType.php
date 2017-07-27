<?php

namespace Sygefor\Bundle\CoreBundle\Form\Type;

use Sygefor\Bundle\CoreBundle\Entity\Term\InscriptionStatus;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class InscriptionStatusVocabularyType.
 */
class InscriptionStatusVocabularyType extends VocabularyType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('notify', CheckboxType::class, array('label' => "Notification lorsqu'une inscription prend ce statut", 'required' => false));
        $builder->add('status', ChoiceType::class, array(
            'label' => 'Statut élémentaire',
            'expanded' => true,
            'multiple' => false,
            'required' => true,
            'choices' => array(
                InscriptionStatus::STATUS_ACCEPTED => 'Accepté',
                InscriptionStatus::STATUS_WAITING => 'En attente',
                InscriptionStatus::STATUS_PENDING => 'En attente de traitement',
                InscriptionStatus::STATUS_REJECTED => 'Rejeté',
            ),
        ));
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return VocabularyType::class;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => InscriptionStatus::class,
        ));
    }
}
