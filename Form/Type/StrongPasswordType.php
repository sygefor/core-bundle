<?php

namespace Sygefor\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Sygefor\Bundle\CoreBundle\Validator\Constraints\StrongPassword;

/**
 * Class StrongPasswordType.
 */
class StrongPasswordType extends AbstractType
{
	/**
	 * @return string|\Symfony\Component\Form\FormTypeInterface|null
	 */
	public function getParent()
    {
        return RepeatedType::class;
    }

	/**
	 * @param OptionsResolver $resolver
	 */
	public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(array(
                'user' => null,
                'type' => PasswordType::class,
                'label' => 'Nouveau mot de passe',
                'first_options' => array(
                    'label' => 'Nouveau mot de passe',
                ),
                'second_options' => array(
                    'label' => 'Répétez le mot de passe',
                ),
                'invalid_message' => 'Les mots de passe ne correspondent pas',
                'constraints' => function (Options $options) {
                    return array(
                        new NotBlank(array('message' => 'empty_password')),
                        new StrongPassword(array(
                            'user' => $options['user'],
                        )),
                    );
                },
            ))
        ;
    }
}
