<?php

namespace Sygefor\Bundle\CoreBundle\Form\Type;

use AppBundle\Entity\Session\Session;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DuplicateType.
 */
class DuplicateType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Session $session */
        $session = isset($options['data']) ? $options['data'] : null;

        $builder->getData();

        $builder->add('name', null, array(
            'label' => 'Intitulé de la session',
            'required' => true
        ))
        ->add('dateBegin', DateType::class, array(
            'label' => 'Date de début',
            'widget' => 'single_text',
            'format' => 'dd/MM/yyyy',
            'required' => true
        ))
        ->add('dateEnd', DateType::class, array(
            'label' => 'Date de fin',
            'widget' => 'single_text',
            'format' => 'dd/MM/yyyy',
            'required' => false
        ));
    }
}
