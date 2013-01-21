<?php

namespace PivotX\Backend\Views;

use \PivotX\Component\Views\AbstractView;

class loadContentMenu extends AbstractView
{
    private $doctrine_registry;
    private $translations_service;
    private $data = false;

    public function __construct($doctrine_registry, $translations_service, $name)
    {
        parent::__construct($name, 'PivotX/Backend');

        $this->doctrine_registry    = $doctrine_registry;
        $this->translations_service = $translations_service;
        $this->description          = 'Load the items for the content menu';

        $this->tags = array(
            'Backend',
            'returnAll'
        );

        $this->long_description = <<<THEEND
This view returns the contents of the Content Menu in the main navigation.
THEEND;

        $this->arguments    = array();
        $this->range_limit  = null;
        $this->range_offset = null;
    }

    /**
     * Build the contents of the menu
     * 
     * @todo verify something with security
     * @todo sort it!
     */
    private function buildContentMenu()
    {
        $data = array();

        $classes = $this->doctrine_registry->getEntityManager()->getMetadataFactory()->getAllMetadata();
        foreach($classes as $class) {
            $_p = explode('\\',$class->name);
            $base_class = $_p[count($_p)-1];

            /*
            if ($base_class == 'TranslationText') {
                echo '<pre>';
                var_dump($class);
                die('stop');
            }
            //*/

            if ($class->name == $class->rootEntityName) {
                $data[] = array(
                    'refText' => '_table/'.$base_class,
                    'refArguments' => array(),
                    'label' => $this->translations_service->translate(mb_strtolower($base_class).'.entity.title')
                );
            }
        }

        return $data;
    }

    public function getResult()
    {
        if ($this->data === false) {
            $this->data = $this->buildContentMenu();
        }

        return $this->data;
    }

    public function getLength()
    {
        return count($this->getResult());
    }
}
