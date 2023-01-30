<?php

namespace Sygefor\Bundle\CoreBundle\Form\Type;

use AppBundle\Entity\Session\Session;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
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
        $session = isset($options['data']['session']) ? $options['data']['session'] : null;
        $offersTheListOfSessions = isset($options['data']['offersTheListOfSessions']) ? $options['data']['offersTheListOfSessions'] : null;
        $hasInscriptions = isset($options['data']['hasInscriptions']) ? $options['data']['hasInscriptions'] : false;

        if ($offersTheListOfSessions) {
            $inscriptionManagementDefaultChoice = 'copy';

            $builder->add('targetSession', EntityType::class, array(
                'label' => 'Choisissez la session cible',
                'class' => Session::class,
                'placeholder' => 'Créer une nouvelle session',
                'choice_label' => function (Session $session) {
                    return 'Session du '.$session->getDateBegin()->format("Y-m-d").' - '.$session->getName();
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

            if ($hasInscriptions) {
                $this->addInscriptionManagementChoices($builder, $inscriptionManagementDefaultChoice);
            }
        } else {
            $inscriptionManagementDefaultChoice = 'none';
            $this->addNameAndDatesFields($builder);

            if ($hasInscriptions) {
                $this->addInscriptionManagementChoices($builder, $inscriptionManagementDefaultChoice);
            }
        }
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
     * @param $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Session::class,
            'allow_extra_fields' => true
        ));
    }
}
