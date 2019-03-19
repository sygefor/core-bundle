<?php

namespace Sygefor\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class UpdatePasswordType.
 */
class UpdatePasswordType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('currentPassword', CurrentPasswordType::class)
            ->add('plainPassword', StrongPasswordType::class);
    }
}