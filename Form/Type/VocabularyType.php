<?php

namespace Sygefor\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Class VocabularyType.
 */
class VocabularyType extends AbstractType
{
    /**
     * @var SecurityContext;
     */
    protected $securityContext;

    /**
     * @param SecurityContext
     */
    public function __construct($securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', null, array(
            'label' => 'Nom',
        ));
    }

    protected function setUp()
    {
        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->getFormFactory();
    }
}
