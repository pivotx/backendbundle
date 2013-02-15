<?php

namespace PivotX\BackendBundle\Controller;

use PivotX\BackendBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use PivotX\CoreBundle\Entity\TranslationText;


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

        $view = $views->findView('Backend/findEntities2');
        if (is_null($view)) {
            return null;
        }

        $view->setArguments(array('verbose' => false, 'name' => $name));

        return $view->getValue();
    }

    private function saveEntity2($entity)
    {
        $json = json_encode($entity->exportPivotConfig());

        $siteoptions = $this->get('pivotx.siteoptions');
        $siteoptions->set('config.entities.'.$entity->getInternalName(), $json, 'application/json', false, false, 'all');
    }

    private function saveEntity($entity)
    {
        $json = json_encode($entity);

        $siteoptions = $this->get('pivotx.siteoptions');
        $siteoptions->set('entities.entity.'.strtolower($entity['name']), $json, 'application/json', false, false, 'all');
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

            $field = new \PivotX\Doctrine\Generator\FieldRepresentation($name);

            $field->setState('new');
            $field->setPivotXType($type);

            if (isset($definition['orm'])) {
            }

            if ($field_arg != '') {
                $field->setArguments($field_arg);
            }
            if ($field_rel != '') {
                $field->setTargetEntity($field_rel);
            }

            $entity->addField($field);

            $this->get('session')->setFlash('notice', 'New field "'.$name.'" for entity "'.$entity->getName().'" has been added.');
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
                        $field->setArguments($field_arg);
                    }
                    if ($field_rel != '') {
                        $field->setTargetEntity($field_rel);
                    }
                }
            }

            $this->get('session')->setFlash('notice', 'Field "'.$orig.'" for entity "'.$entity['name'].'" has been edited.');
        }

        $this->saveEntity2($entity);

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

        if ($entity->deleteField($name)) {
            $this->get('session')->setFlash('notice', 'Field "'.$name.'" for entity "'.$entity->getName().'" has been deleted.');

            $this->saveEntity2($entity);

            return true;
        }
        else {
            $this->get('session')->setFlash('notice', 'Field "'.$name.'" for entity "'.$entity->getName().'" could not been deleted (it was not found).');
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

        $this->saveEntity($entity);

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

            $this->saveEntity($entity);

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

        $routing_generator = new \PivotX\Doctrine\Generator\Routing($this->get('pivotx.siteoptions'), $this->get('pivotx.translations'));
        $sites = $routing_generator->getSites();
        foreach($sites as $site) {
            $suggestions->setTranslationsForNewEntity($this->get('pivotx.translations'), $site, $entity->getInternalName());
        }

        $this->get('session')->setFlash('notice', 'Entity "'.$name.'" has been added.');

        $this->saveEntity2($entity);

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

        $entity->setState('deleted');

        $this->saveEntity2($entity);

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

        $entity->reorderFields($arguments->get('order', array()));

        $this->saveEntity2($entity);

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
        $fields = $entity->getFields();
        foreach($fields as &$field) {
            $field->setInCrud(in_array($field->getName(), $crud_fields));
        }

        $this->saveEntity2($entity);

        return false;
    }

    public function showMutateAction(Request $request)
    {
        $entity_name = $this->getRequest()->request->get('entity');

        switch ($request->request->get('action', '')) {
            case 'add_entity':
                $entity_name = $this->handleNewEntity($request->request);
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

    private function handleEntityForm(Request $request, $entity)
    {
        $variants = array(
            'singular_title' => 'singular title',
            'singular_slug' => 'singular slug',
            'plural_title' => 'plural title',
            'plural_slug' => 'plural slug',
        );

        $entity_name = $entity->getInternalName();

        $default_data = array();

        $routing_generator = new \PivotX\Doctrine\Generator\Routing($this->get('pivotx.siteoptions'), $this->get('pivotx.translations'));

        $sites = $routing_generator->getSites();
        $translations = $this->get('pivotx.translations');

        foreach($sites as $site) {
            $languages = $routing_generator->getLanguagesForSite($site);
            $usite     = str_replace('-', '_', $site);

            foreach($languages as $language) {
                $name = $language['name'];

                foreach($variants as $k => $v) {
                    $default_data[$usite.'_'.$name.'_'.$k] = $translations->translate($entity_name.'.common.'.$k, '&site='.$site.'&language='.$name);
                }

            }
        }

        $builder = $this->createFormBuilder($default_data);
        foreach($sites as $site) {
            $languages = $routing_generator->getLanguagesForSite($site);
            $usite     = str_replace('-', '_', $site);

            foreach($languages as $language) {
                $name = $language['name'];

                foreach($variants as $k => $v) {
                    $builder->add($usite.'_'.$name.'_'.$k, 'text', array(
                        'label' => $site . '/' . $name . ' '.$v,
                        'required' => false
                    ));
                }
            }
        }
        $form = $builder->getForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);

            $data = $form->getData();

            foreach($sites as $site) {
                $languages = $routing_generator->getLanguagesForSite($site);
                $usite     = str_replace('-', '_', $site);

                foreach($variants as $k => $v) {
                    $texts = array();
                    foreach($languages as $language) {
                        $name = $language['name'];

                        $value = $data[$usite.'_'.$name.'_'.$k];
                        if (is_null($value) || (trim($value) == '')) {
                            $value = '';
                        }
                        $texts[$name] = $value;
                    }

                    $translations->setTexts($entity_name, 'common.'.$k, $site, null, $texts, TranslationText::STATE_VALID);
                }

                $routing_generator->updateRoutes($entity_name);
                $site_routing = new \PivotX\Component\Siteoptions\Routing($this->get('pivotx.siteoptions'));
                $site_routing->compileSiteRoutes($site);
            }

            $this->saveEntity2($entity);

            return false;
        }

        return $form;
    }

    public function showEntityAction(Request $request)
    {
        $context = $this->getDefaultHtmlContext();

        $entity_name = $this->getRequest()->attributes->get('entity');

        $view = $this->get('pivotx.views')->findView('Backend/findEntities2');
        $view->setArguments(array('name'=>$entity_name));

        $entity = $view->getValue();

        if (is_null($entity)) {
            $this->get('session')->setFlash('error', 'Entity could not be found.');

            $url = $this->get('pivotx.routing')->buildUrl('_entities/all');
            return $this->redirect($url);
        }

        if (($form = $this->handleEntityForm($request, $entity)) === false) {
            // form submission has been handled

            $url = $this->get('pivotx.routing')->buildUrl('_entity/'.$entity_name);

            return $this->redirect($url);
        }

        $context['entity'] = $entity;
        $context['form']   = $form->createView();

        return $this->render('Entities/entity.html.twig', $context);
    }
}

