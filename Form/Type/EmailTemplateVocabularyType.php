<?php

namespace Sygefor\Bundle\CoreBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Sygefor\Bundle\CoreBundle\Entity\Term\EmailTemplate;
use Sygefor\Bundle\CoreBundle\Entity\Term\InscriptionStatus;
use Sygefor\Bundle\CoreBundle\Entity\Term\PresenceStatus;
use Sygefor\Bundle\CoreBundle\Entity\Term\PublipostTemplate;
use Symfony\Component\Form\FormBuilderInterface;

class EmailTemplateVocabularyType extends VocabularyType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('subject', 'text', array('label' => 'Sujet'));
        $builder->add('cc', 'choice', array(
            'label' => 'CC',
            'multiple' => true,
            'expanded' => true,
            'choices' => array(
                'employer' => 'Employeur',
                'manager' => 'Directeur',
                'trainingCorrespondent' => 'Correspondants formation',
            ),
            'required' => false,
        ));
        $builder->add('body', 'textarea', array('label' => 'Corps', 'attr' => array('rows' => 10)));
        $builder->add('inscriptionStatus', 'entity', array(
            'required' => false,
            'label' => "Status d'inscription",
            'class' => InscriptionStatus::class,
            'empty_value' => '',
            'empty_data' => null,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('i')
                    ->where('i.organization = :orgId')->setParameters(array('orgId' => $this->securityContext->getToken()->getUser()->getOrganization()->getId()))
                    ->orWhere('i.organization is null')
                    ->orderBy('i.name');
            },
        ));
        $builder->add('attachmentTemplates', 'entity', array(
            'required' => false,
            'label' => 'Modèles de pièces jointes',
            'class' => PublipostTemplate::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('d')
                    ->where('d.organization = :orgId')->setParameters(array('orgId' => $this->securityContext->getToken()->getUser()->getOrganization()->getId()))
                    ->orWhere('d.organization is null')
                    ->orderBy('d.name');
            },
            'multiple' => 'true',
            'empty_value' => '',
            'empty_data' => null,
        ));
        $builder->add('presenceStatus', 'entity', array(
            'required' => false,
            'label' => 'Statut de présence',
            'class' => PresenceStatus::class,
            'empty_value' => '',
            'empty_data' => null,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('p')
                    ->where('p.organization = :orgId')->setParameters(array('orgId' => $this->securityContext->getToken()->getUser()->getOrganization()->getId()))
                    ->orWhere('p.organization is null');
            },
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
            'data_class' => EmailTemplate::class,
        ));
    }
}
