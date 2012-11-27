<?php

namespace PivotX\Backend\Views;

use \PivotX\Component\Views\AbstractView;

class findEntities extends AbstractView
{
    private $doctrine_registry;
    private $siteoptions_service;
    private $data = false;

    public function __construct($doctrine_registry, $siteoptions_service, $name)
    {
        parent::__construct($name, 'PivotX/Backend');

        $this->doctrine_registry   = $doctrine_registry;
        $this->siteoptions_service = $siteoptions_service;
        $this->description         = 'Find all the entities and their properties';

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

    /**
     */
    private function createFieldArray($name)
    {
        $field = array(
            'name' => $name,
            'created' => true,
            'type' => '-',
            'type_description' => 'choose type',
            'nullable' => false,
            'unique' => false,
        );

        return $field;
    }

    /**
     * Decode a JSON entity definition
     *
     * In JSON is defined as follows:
     * - string $name
     * - array  $fields
     *   - string   $name
     *   - string   $type
     *   - boolean  $created
     */
    private function decodeJsonEntity($name, $value)
    {
        $entity = array(
            'name' => $name,
            'bundle' => false,
            'fields' => array(),
        );

        if (!isset($this->arguments['verbose']) || ($this->arguments['verbose'] === true)) {
            $entity['mediatype'] = 'application/json';
            $entity['source']    = $value;
        }

        $definition = json_decode($value, true);

        $entity['bundle'] = $definition['bundle'];

        $suggestions = new \PivotX\Doctrine\Generator\Suggestions();

        if (!isset($this->arguments['verbose']) || ($this->arguments['verbose'] === true)) {
            foreach($definition['fields'] as $fieldconfig) {
                $field = $this->createFieldArray($fieldconfig['name']);

                //$field['created'] = true;
                $field['type']     = $fieldconfig['type'];

                // @todo the arguments/relation handling is messy now
                if (isset($fieldconfig['arguments'])) {
                    $field['arguments'] = $fieldconfig['arguments'];
                }
                if (isset($fieldconfig['relation'])) {
                    $field['relation'] = $fieldconfig['relation'];
                }

                if (isset($fieldconfig['created'])) {
                    $field['created']  = $fieldconfig['created'];
                }

                if ($field['type'] == 'entity.id') {
                    // don't ever allow this to be overridden
                    $field['created'] = true;
                }

                $twigfield = $suggestions->getTwigFieldFromType($fieldconfig['type']);
                foreach($twigfield as $k => $v) {
                    $field[$k] = $v;
                }

                $entity['fields'][] = $field;
            }
        }
        else {
            $entity['fields'] = $definition['fields'];
        }

        return $entity;
    }

    /**
     */
    private function decodeYamlEntity($name, $value)
    {
        $entity = array(
            'name' => $name,
            'mediatype' => 'text/x-yaml',
            'source' => $value,
            'fields' => array(),
        );

        $definition = \Symfony\Component\Yaml\Yaml::parse($value);

        $classname = '';
        $config    = array();
        foreach($definition as $_classname => $_config) {
            $classname = $_classname;
            $config    = $_config;
            break;
        }

        foreach($config['fields'] as $fieldid => $fieldconfig) {
            $field = $this->createFieldArray($fieldid);

            $field['created']  = true;
            $field['type']     = $fieldconfig['type'];
            $field['editor']   = 'text';

            $field['type_descriptipon'] = $fieldconfig['type'];

            $field['nullable'] = $fieldconfig['nullable'];
            $field['unique']   = $fieldconfig['unique'];

            $entity['fields'][] = $field;
        }

        return $entity;
    }
    
    /**
     * Load the entity from the configuration
     *
     * Strange fact:
     * - if it's a YAML we assume it's unmanaged by PivotX
     * - if it's a JSON we assume it's managed by PivotX
     */
    private function loadEntity($name)
    {
        $siteoption = $this->siteoptions_service->getSiteOption('entities.entity.'.$name, 'all');
        switch ($siteoption->getMediatype()) {
            case 'text/x-yaml':
                return $this->decodeYamlEntity($name, $siteoption->getValue());
                break;
            case 'application/json':
                return $this->decodeJsonEntity($name, $siteoption->getValue());
                break;
        }

        return null;
    }

    /**
     * Load all the entities from the configuration
     * 
     * @todo verify something with security
     * @todo sort it!
     */
    private function loadEntities()
    {
        $data = array();

        $entities = $this->siteoptions_service->getValue('config.entities', null, 'all');

        foreach($entities as $entity) {
            $record = $this->loadEntity($entity);

            $data[] = $record;
        }

        return $data;
    }

    public function getResult()
    {
        if ($this->data === false) {
            $this->data = $this->loadEntities();
        }

        return $this->data;
    }

    public function getLength()
    {
        return count($this->getResult());
    }
}