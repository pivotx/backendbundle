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
        $builder->setAttribute('slug_entity', $options['slug_entity']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $this->options = $options;

        $view
            ->set('sources', $form->getAttribute('sources'))
            ->set('slug_entity', $form->getAttribute('slug_entity'))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $sources = array();
        if (isset($this->options['sources'])) {
            $sources = $this->options['sources'];
        }

        $resolver->setDefaults(array(
            'compound' => false,
            'sources' => $sources,
            'slug_entity' => $this->options['slug_entity']
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
