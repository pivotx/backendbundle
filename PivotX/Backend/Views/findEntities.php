<?php

namespace PivotX\Backend\Views;

use \PivotX\Component\Views\AbstractView;

/**
 * Find all entities
 *
 * @todo an entity is now a regular array should be upgraded to an object
 */
class findEntities extends AbstractView
{
    private $doctrine_registry;
    private $siteoptions_service;

    private $default_entity_roles;
    private $hardcoded_entities;

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

        $this->default_entity_roles = array(
            'create' => 'ROLE_EDITOR',
            'read'   => 'ROLE_USER',
            'update' => 'ROLE_EDITOR',
            'delete' => 'ROLE_EDITOR',
        );
        $this->hardcoded_entities = array(
            'SiteOption'      => array(
                'create' => 'ROLE_SUPER_ADMIN',
                'read'   => 'ROLE_ADMIN',
                'update' => 'ROLE_SUPER_ADMIN',
                'delete' => 'ROLE_SUPER_ADMIN',
            ),
            'TranslationText' => array(
                'create' => 'ROLE_ADMIN',
                'read'   => 'ROLE_EDITOR',
                'update' => 'ROLE_EDITOR',
                'delete' => 'ROLE_ADMIN',
            ),
            'User'            => array(
                'create' => 'ROLE_ADMIN',
                'read'   => 'ROLE_EDITOR',
                'update' => 'ROLE_ADMIN',
                'delete' => 'ROLE_ADMIN',
            ),
            'ActivityLog'     => array(
                'create' => 'ROLE_SUPER_ADMIN',
                'read'   => 'ROLE_ADMIN',
                'update' => 'ROLE_SUPER_ADMIN',
                'delete' => 'ROLE_SUPER_ADMIN',
            ),
            'GenericResource' => array(
                'create' => 'ROLE_EDITOR',
                'read'   => 'ROLE_EDITOR',
                'update' => 'ROLE_EDITOR',
                'delete' => 'ROLE_EDITOR',
            ),
        );
    }

    /**
     * Create an empty entity array
     */
    private function createEntityArray($name)
    {
        $field = array(
            'name' => $name,
            'roles' => $this->default_entity_roles,
            'fixed' => false,
            'bundle' => false,
            'settings' => false,
            'fields' => array(),
            'features' => array()
        );

        return $field;
    }

    /**
     */
    private function createFieldArray($name)
    {
        $field = array(
            'name' => $name,
            'created' => true,
            'fixed' => false,
            'in_crud' => true,
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
        $entity = $this->createEntityArray($name);

        if (!isset($this->arguments['verbose']) || ($this->arguments['verbose'] === true)) {
            $entity['mediatype'] = 'application/json';
            //$entity['source']    = $value;
            $entity['source']    = false;
        }

        $definition = json_decode($value, true);

        $entity['bundle'] = $definition['bundle'];

        $parts = explode('\\', $definition['bundle']);
        array_pop($parts);
        $entity['entity_class']  = implode('\\',$parts).'\\Entity\\'.$name;
        //$entity['repository_class']  = implode('\\',$parts).'\\Model\\'.$name.'Repository';

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
                if (isset($fieldconfig['fixed'])) {
                    $field['fixed']  = $fieldconfig['fixed'];
                }
                if (isset($fieldconfig['in_crud'])) {
                    $field['in_crud']  = $fieldconfig['in_crud'];
                }

                if ($field['type'] == 'entity.id') {
                    // don't ever allow this to be overridden
                    $field['created'] = true;
                    $field['fixed']   = true;
                }

                $twigfield = $suggestions->getTwigFieldFromType($fieldconfig['type'], $field);
                foreach($twigfield as $k => $v) {
                    $field[$k] = $v;
                }

                $entity['fields'][] = $field;
            }
        }
        else {
            $entity['fields'] = $definition['fields'];
        }

        if (!isset($definition['features'])) {
            $definition['features'] = array();
        }
        $entity['features'] = $definition['features'];

        if (isset($definition['delete'])) {
            $entity['delete'] = true;
        }

        if (isset($definition['settings'])) {
            $entity['settings'] = $definition['settings'];
        }

        return $entity;
    }

    /**
     */
    private function decodeYamlEntity($name, $value)
    {
        $entity = $this->createEntityArray($name);
        $entity['mediatype'] = 'text/x-yaml';
        $entity['source']    = $value;

        $definition = \Symfony\Component\Yaml\Yaml::parse($value);

        $classname = '';
        $config    = array();
        foreach($definition as $_classname => $_config) {
            $classname = $_classname;
            $config    = $_config;
            break;
        }

        $entity['entity_class'] = $classname;

        foreach($config['fields'] as $fieldid => $fieldconfig) {
            $field = $this->createFieldArray($fieldid);

            $field['created']  = true;
            $field['type']     = $fieldconfig['type'];
            $field['editor']   = 'text';

            $field['type_description'] = $fieldconfig['type'];

            $field['nullable'] = $fieldconfig['nullable'];
            $field['unique']   = $fieldconfig['unique'];

            $entity['fields'][] = $field;
        }

        return $entity;
    }

    /**
     */
    private function convertMetadataToEntity($name, $class)
    {
        $entity = $this->createEntityArray($name);
        $entity['fixed']        = true;
        $entity['mediatype']    = 'text/x-yaml';
        $entity['source']       = null;
        $entity['entity_class'] = $class->name;

        foreach($class->fieldMappings as $key => $config) {
            $field = $this->createFieldArray($key);

            $field['created']   = true;
            $field['fixed']     = true;

            $entity['fields'][] = $field;
        }

        return $entity;
    }

    /**
     */
    private function findAndConvertMetadataToEntity($name)
    {
        foreach ($this->doctrine_registry->getEntityManagers() as $em) {
            $classes = $em->getMetadataFactory()->getAllMetadata();
            foreach($classes as $class) {
                $_p = explode('\\',$class->name);
                $base_class = $_p[count($_p)-1];

                if ($base_class == $name) {
                    return $this->convertMetadataToEntity($name, $class);
                }
            }
        }

        return null;
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
        $siteoption = $this->siteoptions_service->getSiteOption('entities.entity.'.strtolower($name), 'all');
        if (is_null($siteoption)) {
            $entity = $this->findAndConvertMetadataToEntity($name);
        }
        else {
            switch ($siteoption->getMediatype()) {
                case 'text/x-yaml':
                    $entity = $this->decodeYamlEntity($name, $siteoption->getValue());
                    break;
                case 'application/json':
                    $entity = $this->decodeJsonEntity($name, $siteoption->getValue());
                    break;
            }
        }

        if (!is_null($entity)) {
            // enforce hardcoded entity roles
            if (isset($this->hardcoded_entities[$entity['name']])) {
                $entity['roles'] = $this->hardcoded_entities[$entity['name']];
            }
        }

        return $entity;
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

        $hardcoded = array_keys($this->hardcoded_entities);
        $entities  = array_merge($entities, $hardcoded);

        foreach($entities as $entity) {
            $record = $this->loadEntity($entity);

            if (is_null($record)) {
                continue;
            }

            if ((!isset($this->arguments['name'])) || $this->arguments['name'] == $record['name']) {
                $data[] = $record;
            }
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
