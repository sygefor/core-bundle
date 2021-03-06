<?php

namespace Sygefor\Bundle\CoreBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\AccessRightRegistry;
use Sygefor\Bundle\CoreBundle\Entity\AbstractOrganization;
use Sygefor\Bundle\CoreBundle\Entity\Term\Title;
use Sygefor\Bundle\CoreBundle\Entity\AbstractTrainer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Class TrainerType.
 */
class AbstractTrainerType extends AbstractType
{
    /** @var SecurityContext $securityContext */
    protected $accessRightsRegistry;

    /**
     * @param AccessRightRegistry $accessRightsRegistry
     */
    public function __construct(AccessRightRegistry $accessRightsRegistry)
    {
        $this->accessRightsRegistry = $accessRightsRegistry;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', EntityType::class, array(
                'label' => 'Civilité',
                'class' => Title::class,
                'required' => true,
            ))
            ->add('firstName', null, array(
                'label' => 'Prénom',
            ))
            ->add('lastName', null, array(
                'label' => 'Nom',
            ))
            ->add('organization', EntityType::class, array(
                'required' => true,
                'class' => AbstractOrganization::class,
                'label' => 'Centre',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('o')->orderBy('o.name', 'ASC');
                },
            ))
            ->add('comments', TextareaType::class, array(
                'label' => 'Commentaires',
            ));

        // If the user does not have the rights, remove the organization field and force the value
        $hasAccessRightForAll = $this->accessRightsRegistry->hasAccessRight('sygefor_core.access_right.trainer.all.create');
        if (!$hasAccessRightForAll) {
            $securityContext = $this->accessRightsRegistry->getSecurityContext();
            $user = $securityContext->getToken()->getUser();
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($user) {
                $trainer = $event->getData();
                $trainer->setOrganization($user->getOrganization());
                $event->getForm()->remove('organization');
            });
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => AbstractTrainer::class,
        ));
    }
}
