<?php

namespace PivotX\Backend\Views;

use \PivotX\Component\Views\AbstractView;

class findBundles extends AbstractView
{
    private $kernel;
    private $data = false;

    public function __construct($kernel, $name)
    {
        parent::__construct($name, 'PivotX/Backend');

        $this->kernel      = $kernel;
        $this->description = 'Find the bundles';

        $this->tags = array(
            'Backend',
            'returnAll'
        );

        $this->long_description = <<<THEEND
This view returns all the bundles.
THEEND;

        $this->arguments    = array();
        $this->range_limit  = null;
        $this->range_offset = null;
    }

    private function loadBundles()
    {
        $data = array();

        $bundles = $this->kernel->getContainer()->getParameter('kernel.bundles');

        foreach($bundles as $bundle) {
            $parts    = explode('\\', $bundle);
            $basename = end($parts);
            $path = $this->kernel->locateResource('@'.$basename.'/'.$basename.'.php');

            $data[] = array(
                'value' => $bundle.' ('.$path.')',
                'title' => $bundle,
            );
        }

        return $data;
    }

    public function getResult()
    {
        if ($this->data === false) {
            $this->data = $this->loadBundles();
        }

        return $this->data;
    }

    public function getLength()
    {
        return count($this->getResult());
    }
}
