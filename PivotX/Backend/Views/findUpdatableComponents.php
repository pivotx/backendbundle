<?php

namespace PivotX\Backend\Views;

use \PivotX\Component\Views\AbstractView;

class findUpdatableComponents extends AbstractView
{
    private $data = false;

    public function __construct($kernel, $name)
    {
        parent::__construct($name, 'PivotX/Backend');

        $this->kernel      = $kernel;
        $this->description = 'Find the updatable components';

        $this->tags = array(
            'Backend',
            'returnAll'
        );

        $this->long_description = <<<THEEND
This view returns all the updatable components.
THEEND;

        $this->arguments    = array();
        $this->range_limit  = null;
        $this->range_offset = null;
    }

    protected function loadComponents()
    {
        $data = array();

        $data[] = array(
            'name' => 'PivotX/Core',
            'component_name' => 'pivotx_core',
            'version' => \PivotX\CoreBundle\CoreBundle::VERSION
        );
        $data[] = array(
            'name' => 'PivotX/Backend',
            'component_name' => 'pivotx_backend',
            'version' => \PivotX\BackendBundle\BackendBundle::VERSION
        );
        $data[] = array(
            'name' => 'Symfony',
            'component_name' => 'symfony_framework',
            'version' => \Symfony\Component\HttpKernel\Kernel::VERSION,
        );
        $data[] = array(
            'name' => 'Twig',
            'component_name' => 'twig',
            'version' => \Twig_Environment::VERSION,
        );

        return $data;
    }

    public function getResult()
    {
        if ($this->data === false) {
            $this->data = $this->loadComponents();
        }

        return $this->data;
    }

    public function getLength()
    {
        return count($this->getResult());
    }
}
