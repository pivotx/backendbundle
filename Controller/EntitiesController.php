<?php

namespace PivotX\BackendBundle\Controller;

use PivotX\BackendBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;


class EntitiesController extends Controller
{
    /**
     * Find the entity configuration
     *
     * @param string $name    name of the entity if null get the 'entity' from the request
     */
    private function getEntityConfiguration($name)
    {
        if (is_null($name)) {
            if ($this->getRequest()->attributes->has('entity')) {
                $name = $this->getRequest()->attributes->get('entity');
            }
        }
        if (is_null($name)) {
            if ($this->getRequest()->request->has('entity')) {
                $name = $this->getRequest()->request->get('entity');
            }
        }

        if (is_null($name)) {
            return null;
        }

        $views = $this->get('pivotx.views');

        $view = $views->findView('Backend/findEntities');
        if (is_null($view)) {
            return null;
        }

        $view->setArguments(array('verbose' => false, 'name' => $name));

        return $view->getValue();
    }

    public function handleEditField(ParameterBag $arguments)
    {
        $orig = trim($arguments->get('original_name', ''));
        $name = trim($arguments->get('name', ''));
        $type = trim($arguments->get('type', ''));

        $field_arg = '';
        $field_rel = '';

        if (($name == '') || ($type == '')) {
            $this->get('session')->setFlash('error', 'Either name or type has not been entered.');
            return false;
        }

        $entity = $this->getEntityConfiguration(null);
        if (is_null($entity)) {
            $this->get('session')->setFlash('error', 'Entity "'.$name.'" doesn\'t exist.');
            return false;
        }

        $suggestions = new \PivotX\Doctrine\Generator\Suggestions();

        $definition = $suggestions->getTwigFieldFromType($type);
        if (isset($definition['needs'])) {
            if ($definition['needs'] == 'arguments') {
                $field_arg = trim($arguments->get('arguments', ''));
            }
            if ($definition['needs'] == 'relation') {
                $field_rel = trim($arguments->get('relation', ''));
            }
        }

        if ($orig == '') {
            // new field

            $field = array(
                'name' => $name,
                'type' => $type,
                'created' => false
            );
            if ($field_arg != '') {
                $field['arguments'] = $field_arg;
            }
            if ($field_rel != '') {
                $field['relation'] = $field_rel;
            }
            $entity['fields'][] = $field;

            $this->get('session')->setFlash('notice', 'New field "'.$name.'" for entity "'.$entity['name'].'" has been added.');
        }
        else {
            // edit field

            foreach($entity['fields'] as &$field) {
                if ($field['name'] == $orig) {
                    if ($field['created'] === true) {
                        $this->get('session')->setFlash('error', 'Field "'.$orig.'" for entity "'.$entity['name'].'" cannot be edited.');
                        return false;
                    }

                    $field['name'] = $name;
                    $field['type'] = $type;
                    if ($field_arg != '') {
                        $field['arguments'] = $field_arg;
                    }
                    if ($field_rel != '') {
                        $field['relation'] = $field_rel;
                    }
                }
            }

            $this->get('session')->setFlash('notice', 'Field "'.$orig.'" for entity "'.$entity['name'].'" has been edited.');
        }

        $json = json_encode($entity);

        $siteoptions = $this->get('pivotx.siteoptions');
        $siteoptions->set('entities.entity.'.$entity['name'], $json, 'application/json', false, false, 'all');

        //$this->get('session')->setFlash('debug', var_export($entity, true));

        return true;
    }

    public function handleDeleteField(ParameterBag $arguments)
    {
        $name = trim($arguments->get('name', ''));

        if ($name == '') {
            $this->get('session')->setFlash('error', 'Name has not been entered.');
            return false;
        }

        $entity = $this->getEntityConfiguration(null);
        if (is_null($entity)) {
            $this->get('session')->setFlash('error', 'Entity "'.$entity['name'].'" doesn\'t exist.');
            return false;
        }

        $idx = false;
        for($i=0; $i < count($entity['fields']); $i++) {
            if ($entity['fields'][$i]['name'] == $name) {
                $idx = $i;
                break;
            }
        }

        if ($idx !== false) {
            array_splice($entity['fields'], $idx, 1);

            $this->get('session')->setFlash('notice', 'Field "'.$name.'" for entity "'.$entity['name'].'" has been deleted.');

            $json = json_encode($entity);

            $siteoptions = $this->get('pivotx.siteoptions');
            $siteoptions->set('entities.entity.'.$entity['name'], $json, 'application/json', false, false, 'all');

            return true;
        }
        else {
            $this->get('session')->setFlash('notice', 'Field "'.$name.'" for entity "'.$entity['name'].'" could not been deleted (it was not found).');
        }

        return false;
    }

    public function handleEditFeature(ParameterBag $arguments)
    {
        $type = trim($arguments->get('type', ''));
        $args = trim($arguments->get('arguments', ''));

        if ($type == '') {
            $this->get('session')->setFlash('error', 'Type has not been entered.');
            return false;
        }

        $entity = $this->getEntityConfiguration(null);
        if (is_null($entity)) {
            $this->get('session')->setFlash('error', 'Entity "'.$name.'" doesn\'t exist.');
            return false;
        }

        $suggestions = new \PivotX\Doctrine\Generator\Suggestions();

        $feature = $suggestions->getFeature($type, $args);

        if (true) {
            // new feature

            $entity['features'][] = $feature;

            $this->get('session')->setFlash('notice', 'New feature "'.$type.'" for entity "'.$entity['name'].'" has been added.');
        }

        $json = json_encode($entity);

        $siteoptions = $this->get('pivotx.siteoptions');
        $siteoptions->set('entities.entity.'.$entity['name'], $json, 'application/json', false, false, 'all');

        //$this->get('session')->setFlash('debug', var_export($entity, true));

        return true;
    }

    public function handleDeleteFeature(ParameterBag $arguments)
    {
        $type = trim($arguments->get('type', ''));

        if ($type == '') {
            $this->get('session')->setFlash('error', 'Type has not been entered.');
            return false;
        }

        $entity = $this->getEntityConfiguration(null);
        if (is_null($entity)) {
            $this->get('session')->setFlash('error', 'Entity "..." doesn\'t exist.');
            return false;
        }

        $idx = false;
        for($i=0; $i < count($entity['features']); $i++) {
            if ($entity['features'][$i]['type'] == $type) {
                $idx = $i;
                break;
            }
        }

        if ($idx !== false) {
            array_splice($entity['features'], $idx, 1);

            $this->get('session')->setFlash('notice', 'Feature "'.$type.'" for entity "'.$entity['name'].'" has been deleted.');

            $json = json_encode($entity);

            $siteoptions = $this->get('pivotx.siteoptions');
            $siteoptions->set('entities.entity.'.$entity['name'], $json, 'application/json', false, false, 'all');

            return true;
        }

        return false;
    }

    public function handleNewEntity(ParameterBag $arguments)
    {
        $name   = trim($arguments->get('name', ''));
        $type   = trim($arguments->get('entity_type', ''));
        $bundle = trim($arguments->get('bundle', ''));

        if (($name == '') || ($type == '') || ($bundle == '')) {
            $this->get('session')->setFlash('error', 'Either name or type has not been entered.');
            return false;
        }

        $check_entity = $this->getEntityConfiguration($name);
        if (!is_null($check_entity)) {
            $this->get('session')->setFlash('error', 'Entity "'.$name.'" already exists.');
            return false;
        }

        $suggestions = new \PivotX\Doctrine\Generator\Suggestions();
        $entity = $suggestions->buildEntity($type, $name, $bundle);
        if (is_null($entity)) {
            $this->get('session')->setFlash('error', 'Entity type "'.$type.'" is not available.');
            return false;
        }

        $this->get('session')->setFlash('notice', 'Entity "'.$name.'" has been added.');

        $json = json_encode($entity);

        $siteoptions = $this->get('pivotx.siteoptions');
        $siteoptions->set('entities.entity.'.$entity['name'], $json, 'application/json', false, false, 'all');

        return $name;
    }

    public function handleDeleteEntity(ParameterBag $arguments)
    {
        $entity = $this->getEntityConfiguration(null);
        if (is_null($entity)) {
            $name = $this->getRequest()->request->get('entity');
            $this->get('session')->setFlash('error', 'Entity "'.$name.'" doesn\'t exist.');
            return false;
        }

        $entity['delete'] = true;
        $entity['fields'] = array();

        $json = json_encode($entity);

        $siteoptions = $this->get('pivotx.siteoptions');
        $siteoptions->set('entities.entity.'.$entity['name'], $json, 'application/json', false, false, 'all');

        return false;
    }

    public function handleSortEntity(ParameterBag $arguments)
    {
        $entity = $this->getEntityConfiguration(null);
        if (is_null($entity)) {
            $name = $this->getRequest()->request->get('entity');
            $this->get('session')->setFlash('error', 'Entity "'.$name.'" doesn\'t exist.');
            return false;
        }

        $field_order = $arguments->get('order', array());
        $old_fields  = $entity['fields'];
        $new_fields  = array();
        foreach($field_order as $field) {
            $idx = false;
            for($i=0; $i < count($old_fields); $i++) {
                if ($old_fields[$i]['name'] == $field) {
                    $idx = $i;
                    break;
                }
            }
            if ($idx !== false) {
                $new_fields[] = $old_fields[$idx];
                array_splice($old_fields, $idx, 1);
            }
        }

        $entity['fields'] = array_merge($new_fields, $old_fields);

        $json = json_encode($entity);

        $siteoptions = $this->get('pivotx.siteoptions');
        $siteoptions->set('entities.entity.'.$entity['name'], $json, 'application/json', false, false, 'all');

        return false;
    }

    public function handleCrudCheckEntity(ParameterBag $arguments)
    {
        $entity = $this->getEntityConfiguration(null);
        if (is_null($entity)) {
            $name = $this->getRequest()->request->get('entity');
            $this->get('session')->setFlash('error', 'Entity "'.$name.'" doesn\'t exist.');
            return false;
        }

        $crud_fields = explode(',', $arguments->get('crud'));
        foreach($entity['fields'] as &$field) {
            $field['in_crud'] = in_array($field['name'], $crud_fields);
        }

        $json = json_encode($entity);

        $siteoptions = $this->get('pivotx.siteoptions');
        $siteoptions->set('entities.entity.'.$entity['name'], $json, 'application/json', false, false, 'all');

        return false;
    }

    public function showMutateAction(Request $request)
    {
        $entity_name = $this->getRequest()->request->get('entity');

        switch ($request->request->get('action', '')) {
            case 'add_entity':
                $entity_name = $this->handleNewEntity($request->request);
                $entity_name = false;
                // @todo we cannot redirect to entity until the setup has been updated
                break;

            case 'delete_entity':
                $this->handleDeleteEntity($request->request);
                $entity_name = null;
                break;

            case 'sort_entity':
                $this->handleSortEntity($request->request);
                $notification = json_encode(array(
                    'title' => 'Success',
                    'text' => 'The new order has been saved.',
                    'type' => 'success',
                ));
                return new \Symfony\Component\HttpFoundation\Response($notification, 200, array('X-Notification' => 'yes'));
                break;

            case 'crudcheck_entity':
                $this->handleCrudCheckEntity($request->request);
                $notification = json_encode(array(
                    'title' => 'Success',
                    'text' => 'CRUD settings have been saved.',
                    'type' => 'success',
                ));
                return new \Symfony\Component\HttpFoundation\Response($notification, 200, array('X-Notification' => 'yes'));
                break;

            case 'edit_field':
                $this->handleEditField($request->request);
                break;

            case 'delete_field':
                $this->handleDeleteField($request->request);
                break;

            case 'edit_feature':
                $this->handleEditFeature($request->request);
                break;

            case 'delete_feature':
                $this->handleDeleteFeature($request->request);
                break;
        }

        if ((!is_null($entity_name)) && ($entity_name !== false)) {
            $url = $this->get('pivotx.routing')->buildUrl('_entity/'.$entity_name);
        }
        else {
            $url = $this->get('pivotx.routing')->buildUrl('_entities/all');
        }

        $siteoptions = $this->get('pivotx.siteoptions');
        $siteoptions->set('config.check.entities', 1, 'x-value/boolean', false, false, 'all');
        $siteoptions->set('config.check.any', 1, 'x-value/boolean', false, false, 'all');

        if ($request->getMethod() == 'POST') {
            return $this->redirect($url);
        }

        return new \Symfony\Component\HttpFoundation\Response('', 204, array('X-Location' => $url));
    }

    public function showEntitiesAction(Request $request)
    {
        $context = $this->getDefaultHtmlContext();

        return $this->render('Entities/entities.html.twig', $context);
    }

    public function showEntityAction(Request $request)
    {
        $context = $this->getDefaultHtmlContext();

        $entity_name = $this->getRequest()->attributes->get('entity');

        $view = $this->get('pivotx.views')->findView('Backend/findEntities');
        $view->setArguments(array('name'=>$entity_name));

        $entity = $view->getValue();

        $context['entity'] = $entity;

        return $this->render('Entities/entity.html.twig', $context);
    }
}

