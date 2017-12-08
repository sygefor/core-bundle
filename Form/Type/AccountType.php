<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 11/22/17
 * Time: 4:15 PM.
 */

namespace Sygefor\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Sygefor\Bundle\CoreBundle\Entity\User;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AccountType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', 'text', array(
                'constraints' => new Length(array('min' => 5)),
                'invalid_message' => 'Le nom d\'utilisateur est trop court',
                'label' => 'Nom d\'utilisateur',
            ))
            ->add('email', 'email', array(
                'constraints' => new Email(array('message' => 'Invalid email address')),
                'label' => 'Email',
            ))
            ->add('plainPassword', 'repeated', array(
                'type' => 'password',
                'constraints' => new Length(array('min' => 8)),
                'invalid_message' => 'Les mots de passe doivent correspondre',
                'first_options' => array('label' => 'Mot de passe'),
                'second_options' => array('label' => 'Confirmation'),
            )
        );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => User::class,
        ));
    }
}
