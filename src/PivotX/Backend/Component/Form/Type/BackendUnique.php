<?php
namespace PivotX\Backend\Component\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use PivotX\Backend\Component\Form\DataTransformer\ResourceToFieldTransformer;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BackendUnique extends AbstractType
{
    private $entity_manager;
    private $options;

    public function __construct($entity_manager)
    {
        $this->entity_manager = $entity_manager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('sources', $options['sources']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        // @todo guessing this is new
        $this->options = $options;

        $view
            ->set('sources', $form->getAttribute('sources'))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'sources' => $this->options['sources']
        ));
    }

    public function getParent()
    {
        return 'text';
    }

    public function getName()
    {
        return 'backend_unique';
    }
}