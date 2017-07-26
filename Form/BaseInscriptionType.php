<?php

namespace Sygefor\Bundle\CoreBundle\Form;

use Doctrine\ORM\EntityRepository;
use Sygefor\Bundle\CoreBundle\Form\Type\EntityHiddenType;
use Sygefor\Bundle\CoreBundle\Entity\AbstractInscription;
use Sygefor\Bundle\CoreBundle\Entity\AbstractSession;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class BaseInscriptionType.
 */
class BaseInscriptionType extends AbstractType
{
    protected $organization;

    public function __construct($organization)
    {
        $this->organization = $organization;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $organization = $this->organization;
        /** @var AbstractSession $session */
        $session = $options['data']->getSession();

        $builder
            ->add('trainee', EntityHiddenType::class, array(
                'label' => 'Stagiaire',
                'class' => 'SygeforCoreBundle:AbstractTrainee',
                'invalid_message' => '',
            ))
            ->add('session', EntityHiddenType::class, array(
                'label' => 'Session',
                'class' => 'SygeforCoreBundle:Session\AbstractSession',
                'invalid_message' => 'Session non reconnue',
            ))
            ->add('inscriptionStatus', EntityType::class, array(
                'label' => 'Status d\'inscription',
                'class' => 'SygeforCoreBundle:Term\InscriptionStatus',
                'query_builder' => function (EntityRepository $repository) use ($organization) {
                    $qb = $repository->createQueryBuilder('i');
                    $qb->where('i.organization = :organization')
                        ->setParameter('organization', $organization)
                        ->orWhere('i.organization is null');

                    return $qb;
                },
            ))
            ->add('presenceStatus', EntityType::class, array(
                'label' => 'Statut de présence',
                'class' => 'SygeforCoreBundle:Term\PresenceStatus',
                'query_builder' => function (EntityRepository $repository) use ($organization) {
                    $qb = $repository->createQueryBuilder('i');
                    $qb->where('i.organization = :organization')
                        ->setParameter('organization', $organization)
                        ->orWhere('i.organization is null');

                    return $qb;
                },
            ));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => AbstractInscription::class,
        ));
    }
}
