<?php
namespace PivotX\Backend\Component\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use PivotX\Backend\Component\Form\DataTransformer\ResourceToFieldTransformer;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BackendResource extends AbstractType
{
    private $entity_manager;
    private $options;

    public function __construct($entity_manager)
    {
        $this->entity_manager = $entity_manager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('files', $options['files']);

        $transformer = new ResourceToFieldTransformer($this->entity_manager);
        $builder->prependClientTransformer($transformer);

        //var_dump($options); echo "<br/>\n";

        // add a normal text field, but add our transformer to it
        //*
        $builder->add(
            $builder->create('filesinfo', 'hidden')
        );
        //*/
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        // @todo guessing this is new
        $this->options = $options;

        $view
            ->set('files', $form->getAttribute('files'))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'multiple' => false,
            'files' => $this->options['files']
        ));
    }

    public function getParent()
    {
        return 'text';
    }

    public function getName()
    {
        return 'backend_resource';
    }
}
