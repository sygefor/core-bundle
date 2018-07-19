<?php

/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 7/5/16
 * Time: 2:39 PM.
 */

namespace Sygefor\Bundle\CoreBundle\Form\Type;

use Sygefor\Bundle\CoreBundle\Entity\AbstractSession;
use Sygefor\Bundle\CoreBundle\Entity\AbstractTrainer;
use Sygefor\Bundle\CoreBundle\Entity\AbstractParticipation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AbstractParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $notBlank = new NotBlank(array('message' => 'Vous devez sélectionner une session.'));
        $notBlank->addImplicitGroupName('session_add');

        $builder
            ->add('trainer', EntityHiddenType::class, array(
                'label' => 'Intervenant',
                'class' => AbstractTrainer::class,
                'constraints' => new NotBlank(array('message' => 'Vous devez sélectionner un intervenant.')),
            ))
            ->add('session', EntityHiddenType::class, array(
                'label' => 'Session',
                'class' => AbstractSession::class,
                'constraints' => $notBlank,
            ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => AbstractParticipation::class,
        ));
    }
}
