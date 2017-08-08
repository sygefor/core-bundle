<?php

namespace Sygefor\Bundle\CoreBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Sygefor\Bundle\CoreBundle\Entity\Term\EmailTemplate;
use Sygefor\Bundle\CoreBundle\Entity\Term\InscriptionStatus;
use Sygefor\Bundle\CoreBundle\Entity\Term\PresenceStatus;
use Sygefor\Bundle\CoreBundle\Entity\Term\PublipostTemplate;
use Sygefor\Bundle\CoreBundle\Utils\Email\CCRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EmailTemplateVocabularyType extends VocabularyType
{
    /** @var CCRegistry */
    protected $ccRegistry;

    /**
     * @param CCRegistry
     */
    public function setCCRegistry($ccRegistry)
    {
        $this->ccRegistry = $ccRegistry;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('subject', TextType::class, array(
                'label' => 'Sujet',
            ))
            ->add('cc', ChoiceType::class, array(
                'label' => 'CC',
                'multiple' => true,
                'expanded' => true,
                'choices' => $this->ccRegistry->getSupportedResolvers(),
                'required' => false,
            ))
            ->add('body', TextareaType::class, array(
                'label' => 'Corps',
                'attr' => array(
                    'rows' => 10,
                ), ))
            ->add('inscriptionStatus', EntityType::class, array(
                'label' => "Status d'inscription",
                'class' => InscriptionStatus::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('i')
                        ->where('i.organization = :orgId')
                        ->orWhere('i.organization is null')
                        ->orderBy('i.name')
                        ->setParameter('orgId', $this->securityContext->getToken()->getUser()->getOrganization()->getId());
                },
                'required' => false,
            ))
            ->add('attachmentTemplates', EntityType::class, array(
                'label' => 'Modèles de pièces jointes',
                'class' => PublipostTemplate::class,
                'multiple' => 'true',
                'query_builder' => function (EntityRepository $er) use ($options) {
                    $data = $options['data'];
                    $organization = null;
                    if ($data && $data->getOrganization()) {
                        $organization = $data->getOrganization();
                    }

                    return $er->createQueryBuilder('d')
                        ->orWhere('d.organization = :organization')
                        ->orWhere('d.organization is null')
                        ->setParameter('organization', $organization->getId())
                        ->orderBy('d.name');
                },
                'required' => false,
            ))
            ->add('presenceStatus', EntityType::class, array(
                'label' => 'Statut de présence',
                'class' => PresenceStatus::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.organization = :orgId')
                        ->orWhere('p.organization is null')
                        ->setParameter('orgId', $this->securityContext->getToken()->getUser()->getOrganization()->getId());
                },
                'required' => false,
            ))
        ;
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
