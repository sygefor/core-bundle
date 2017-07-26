<?php

/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 15/04/14
 * Time: 14:30.
 */

namespace Sygefor\Bundle\CoreBundle\Form;

use Sygefor\Bundle\CoreBundle\Form\Type\EntityHiddenType;
use Sygefor\Bundle\CoreBundle\Entity\AbstractSession;
use Sygefor\Bundle\CoreBundle\Entity\AbstractTraining;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class AbstractSessionType.
 */
class AbstractSessionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('training', EntityHiddenType::class, array(
                'label' => 'Formation',
                'class' => AbstractTraining::class,
                'required' => true,
            ))
            ->add('registration', ChoiceType::class, array(
                'label' => 'Inscriptions',
                'choices' => array(
                    AbstractSession::REGISTRATION_DEACTIVATED => 'Désactivées',
                    AbstractSession::REGISTRATION_CLOSED => 'Fermées',
                    AbstractSession::REGISTRATION_PRIVATE => 'Privées',
                    AbstractSession::REGISTRATION_PUBLIC => 'Publiques',
                ),
                'required' => true,
            ))
            ->add('displayOnline', ChoiceType::class, array(
                'label' => 'Afficher en ligne',
                'choices' => array(
                    0 => 'Non',
                    1 => 'Oui',
                ),
                'required' => false,
            ))
            ->add('dateBegin', DateType::class, array(
                'label' => 'Date de début',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => true,
            ))
            ->add('dateEnd', DateType::class, array(
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => false,
            ))
            ->add('status', ChoiceType::class, array(
                'label' => 'Statut',
                'choices' => array(
                    AbstractSession::STATUS_OPEN => 'Ouverte',
                    AbstractSession::STATUS_REPORTED => 'Reportée',
                    AbstractSession::STATUS_CANCELED => 'Annulée',
                ),
                'required' => false,
            ))
            ->add('sessionType', EntityType::class, array(
                'label' => 'Type',
                'class' => Type::class,
                'required' => false,
            ))
            ->add('numberOfRegistrations', null, array(
                'label' => "Nombre d'inscrits",
                'required' => false,
            ))
            ->add('maximumNumberOfRegistrations', null, array(
                'label' => 'Participants max.',
                'required' => true,
            ))
            ->add('limitRegistrationDate', 'date', array(
                'label' => "Date limite d'inscription",
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => true,
            ))
            ->add('comments', 'textarea', array(
                'required' => false,
                'label' => 'Commentaires',
            ));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => AbstractSession::class,
        ));
    }
}
