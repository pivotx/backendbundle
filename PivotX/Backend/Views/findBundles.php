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

    private function loadBundles($only_src = true)
    {
        $data = array();

        $bundles = $this->kernel->getContainer()->getParameter('kernel.bundles');

        $cwd = dirname(getcwd());
        foreach($bundles as $bundle) {
            $parts    = explode('\\', $bundle);
            $basename = end($parts);
            $path     = $this->kernel->locateResource('@'.$basename.'/'.$basename.'.php');
            $relpath  = str_replace($cwd, '', $path);

            if ((!$only_src) || (substr($relpath, 0, 4) == '/src')) {
                $data[] = array(
                    'value' => $bundle,
                    'path' => $path,
                    'relpath' => $relpath,
                    'title' => $bundle,
                );
            }
        }

        usort($data, function($a, $b){
            return strcmp($a['relpath'], $b['relpath']);
        });

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
