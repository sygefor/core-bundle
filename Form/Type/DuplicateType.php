<?php

namespace Sygefor\Bundle\CoreBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Sygefor\Bundle\CoreBundle\Entity\AbstractSession;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        /** @var AbstractSession $session */
        $session = isset($options['data']) ? $options['data'] : null;
        $originOfDuplication = $options['origin_of_duplication'];
        $inscriptionManagementDefaultChoice = $options['inscription_management_default_choice'];
        $hasInscriptions = count($session->getInscriptions()) > 0 ? true : false;

        // When we come from the list of inscriptions
        if ($originOfDuplication == 'listOfInscriptions') {
            $builder->add('targetSession', EntityType::class, array(
                'label' => 'Choisissez la session cible',
                'class' => AbstractSession::class,
                'placeholder' => 'Créer une nouvelle session',
                'choice_label' => function (AbstractSession $session) {
                    return 'Session du '.$session->getDateBegin()->format("d-m-Y");
                },
                'choice_value' => 'id',
                'query_builder' => function (EntityRepository $er) use ($session) {
                    $qb = $er->createQueryBuilder('s')
                        ->where('s.training = :trainingId')
                        ->orderBy('s.dateBegin', 'DESC')
                        ->setParameter('trainingId', $session->getTraining());
                    return $qb;
                },
                'required' => false
            ));
        }

        // When we come from a session
        if ($originOfDuplication == 'session') {
            $this->addNameAndDatesFields($builder);
        }

        if ($hasInscriptions) {
            $this->addInscriptionManagementChoices($builder, $inscriptionManagementDefaultChoice);
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'preSubmit'), $hasInscriptions);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addNameAndDatesFields($builder)
    {		    
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

    /**
     * @param FormBuilderInterface $builder
     * @param string               $inscriptionManagementDefaultChoice
     */
    protected function addInscriptionManagementChoices($builder, $inscriptionManagementDefaultChoice)
    {
        $builder->add('inscriptionManagement', ChoiceType::class, array(
            'label' => 'Choisir la méthode d\'importation des inscriptions',
            'mapped' => false,
            'choices' => array(
                'none' => 'Ne pas importer les inscriptions',
                'copy' => 'Copier les inscriptions',
                'move' => 'Déplacer les inscriptions',
            ),
            'empty_data' => $inscriptionManagementDefaultChoice,
            'required' => true,
        ));
    }

    /**
     * @param FormEvent $event
     * @param bool      $hasInscriptions
     */
    public function preSubmit(FormEvent $event, $hasInscriptions)
    {
        $form = $event->getForm();
        $formData = $event->getData();
        $targetSession = $formData['targetSession'];

        if ($targetSession) {
            $form->remove('name');
            $form->remove('dateBegin');
            $form->remove('dateEnd');

            if ($hasInscriptions) {
                $inscriptionManagementDefaultChoice = 'copy';
                $this->addInscriptionManagementChoices($form, $inscriptionManagementDefaultChoice);
            }
        }
    }

    /**
     * @param $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'origin_of_duplication' => 'session',
            'inscription_management_default_choice' => 'none',
            'allow_extra_fields' => true
        ));
    }
}
