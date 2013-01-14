<?php
namespace PivotX\Backend\Component\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use PivotX\Backend\Component\Form\DataTransformer\FileToFieldTransformer;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BackendFile extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $files = array();
        if (isset($options['files'])) {
            $files = $options['files'];
        }
        $builder->setAttribute('files', $files);

        $transformer = new FileToFieldTransformer();
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
        $this->options = $options;

        $view
            ->set('files', $form->getAttribute('files'))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $files = array();
        if (isset($this->options['files'])) {
            $files = $this->options['files'];
        }
        $resolver->setDefaults(array(
            'compound' => true,
            'multiple' => false,
            'files' => $files
        ));
    }

    public function getParent()
    {
        return 'text';
    }

    public function getName()
    {
        return 'backend_file';
    }
}
