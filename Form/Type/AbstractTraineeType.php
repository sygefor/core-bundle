<?php

namespace Sygefor\Bundle\CoreBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Sygefor\Bundle\CoreBundle\Entity\Term\Title;
use Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\AccessRightRegistry;
use Sygefor\Bundle\CoreBundle\Entity\AbstractOrganization;
use Sygefor\Bundle\CoreBundle\Entity\AbstractTrainee;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class TraineeType.
 */
class AbstractTraineeType extends AbstractType
{
    /** @var AccessRightRegistry $accessRightsRegistry */
    protected $accessRightsRegistry;

    /**InscriptionListener
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
        parent::buildForm($builder, $options);

        $builder
            ->add('title', EntityType::class, array(
                'class' => Title::class,
                'label' => 'Civilité',
            ))
            ->add('lastName', null, array(
                'label' => 'Nom',
            ))
            ->add('firstName', null, array(
                'label' => 'Prénom',
            ))
            ->add('organization', EntityType::class, array(
                'label' => 'Centre',
                'class' => AbstractOrganization::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('o')->orderBy('o.name', 'ASC');
                },
            ));

        if ($options['enable_security_check']) {
            // If the user does not have the rights, remove the organization field and force the value
            $hasAccessRightForAll = $this->accessRightsRegistry->hasAccessRight('sygefor_core.access_right.trainee.all.create');
            if (!$hasAccessRightForAll) {
                $securityContext = $this->accessRightsRegistry->getSecurityContext();
                $user = $securityContext->getToken()->getUser();
                if (is_object($user)) {
                    $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($user) {
                        $trainee = $event->getData();
                        $trainee->setOrganization($user->getOrganization());
                        $event->getForm()->remove('organization');
                    });
                }
            }
        }
    }

    /**
     * @param $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => AbstractTrainee::class,
            'validation_groups' => array('Default', 'trainee'),
            'enable_security_check' => true,
        ));
    }
}
