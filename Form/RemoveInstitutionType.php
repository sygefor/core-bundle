<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 1/26/17
 * Time: 2:45 PM.
 */

namespace Sygefor\Bundle\CoreBundle\Form;

use Doctrine\ORM\EntityRepository;
use Sygefor\Bundle\CoreBundle\Entity\AbstractInstitution;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class RemoveInstitutionType extends AbstractType
{
    /**
     * @var AbstractInstitution
     */
    protected $institution;

    /**
     * @var int
     */
    protected $institutionTrainees;

    public function __construct(AbstractInstitution $institution, $institutionTrainees)
    {
        $this->institution = $institution;
        $this->institutionTrainees = $institutionTrainees;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $institution = $this->institution;
        $builder
            ->add('replacement', EntityType::class, array(
                'label' => 'Centre de remplacement',
                'class' => AbstractInstitution::class,
                'query_builder' => function (EntityRepository $er) use ($institution) {
                    return $er->createQueryBuilder('i')
                        ->where('i.organization = :organization')
                        ->andWhere('i.id != :id')
                        ->orderBy('i.name', 'ASC')
                        ->setParameter('organization', $institution->getOrganization())
                        ->setParameter('id', $institution->getId());
                },
                'required' => true,
            ));

        if ($this->institutionTrainees > 0) {
            $builder->get('replacement')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                if (!$event->getForm()->getData()) {
                    $event->getForm()->addError(new FormError('Vous devez sélectionner un établissement'));
                }
            });
        }
    }
}
