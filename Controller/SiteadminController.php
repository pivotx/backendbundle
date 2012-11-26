<?php

namespace PivotX\BackendBundle\Controller;

use PivotX\BackendBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;


class SiteadminController extends Controller
{
    /**
     * Find the entity configuration
     *
     * @param string $name    name of the entity if null get the 'entity' from the request
     */
    private function getEntityConfiguration($name)
    {
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
        $view->setArguments(array('verbose' => false));

        foreach($view->getResult() as $entity) {
            if ($entity['name'] == $name) {
                return $entity;
            }
        }

        return null;
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
            $this->get('session')->setFlash('error', 'Entity "'.$entity['name'].'" doesn\'t exist.');
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
                $field['argument'] = $field_arg;
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

        return false;
    }

    public function handleNewEntity(ParameterBag $arguments)
    {
        $name = trim($arguments->get('name', ''));
        $type = trim($arguments->get('entity_type', ''));

        if (($name == '') || ($type == '')) {
            $this->get('session')->setFlash('error', 'Either name or type has not been entered.');
            return false;
        }

        $entity = $this->getEntityConfiguration($name);
        if (!is_null($entity)) {
            $this->get('session')->setFlash('error', 'Entity "'.$name.'" already exists.');
            return false;
        }

        $this->get('session')->setFlash('notice', 'Entity "'.$name.'" has been added.');

        return true;
    }

    public function showMutateAction(Request $request)
    {
        $url = $this->get('pivotx.routing')->buildUrl('_siteadmin/entities');

        switch ($request->request->get('action', '')) {
            case 'add_entity':
                $this->handleNewEntity($request->request);
                break;

            case 'edit_field':
                $this->handleEditField($request->request);
                break;

            case 'delete_field':
                // @todo flash vars don't work because we redirect twice
                $this->handleDeleteField($request->request);
                break;
        }

        return $this->redirect($url);
    }

    public function showEntitiesAction(Request $request)
    {
        $context = $this->getDefaultHtmlContext();

        return $this->render('Siteadmin/entities.html.twig');
    }
}

