<?php

namespace PivotX\Backend\Views;

use \PivotX\Component\Views\AbstractView;
use \PivotX\Doctrine\Generator\EntitiesRepresentation;

/**
 * Find all entities
 */
class findEntities2 extends AbstractView
{
    private $er = false;
    private $entities = false;
    private $data = false;

    private $kernel = false;
    private $doctrine = false;
    private $siteoptions = false;

    public function __construct($kernel, $doctrine, $siteoptions, $name)
    {
        parent::__construct($name, 'PivotX/Backend');

        $this->kernel      = $kernel;
        $this->doctrine    = $doctrine;
        $this->siteoptions = $siteoptions;
        $this->description = 'Find all the entities and their properties';

        $this->tags = array(
            'Backend',
            'returnAll'
        );

        $this->long_description = <<<THEEND
This view returns all the entities for use in the entity-editor.
THEEND;

        $this->arguments    = array();
        $this->range_limit  = null;
        $this->range_offset = null;
    }

    public function getResult()
    {
        if ($this->er === false) {
            $this->er = new EntitiesRepresentation();
            $this->er->importDoctrineConfiguration($this->doctrine, $this->kernel);
            $this->er->importPivotConfiguration($this->siteoptions);

            $this->entities = $this->er->getEntities();
        }

        if ($this->data === false) {
            $this->data = array();
            if (isset($this->arguments['name'])) {
                foreach($this->entities as $entity) {
                    if ($entity->getName() == $this->arguments['name']) {
                        $this->data[] = $entity;
                    }
                }
            }
            else {
                $this->data = $this->entities;
            }
        }

        return $this->data;
    }

    public function getLength()
    {
        return count($this->getResult());
    }
}
