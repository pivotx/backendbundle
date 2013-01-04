<?php

namespace PivotX\BackendBundle\Controller;

use PivotX\BackendBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\MinLength;
use Symfony\Component\Validator\Constraints\Collection as Collection;
use Doctrine\ORM\Mapping\ClassMetadataInfo;


class CrudController extends Controller
{
    public function getDefaultHtmlContext()
    {
        $context = parent::getDefaultHtmlContext();

        $name = $this->getRequest()->get('entity');

        $context['crud'] = array(
            'htmlid' => 'id'.rand(100,999),
            'entity' => $this->loadEntity($name),
            'fields' => $this->getEntityFields($this->getEntityClass($name))
        );

        return $context;
    }

    protected function getEntityClass($name)
    {
        $ems = $this->container->get('doctrine')->getEntityManagers();
        foreach ($ems as $em) {
            $classes = $em->getMetadataFactory()->getAllMetadata();
            foreach($classes as $class) {
                //echo "Class: ".$class->name."\n";
                //var_dump($class);

                $_p = explode('\\',$class->name);
                $base_class = end($_p);

                //echo 'Base-class: '.$base_class."\n";

                if ($base_class == $name) {
                    return $class->name;
                }

                //var_dump($paths,$base_class);
            }
        }

        return null;
    }

    protected function getEntityFields($class_name)
    {
        $entity_manager = $this->get('doctrine')->getEntityManager();

        $fields = array();

        if ($entity_manager) {
            $item = new $class_name;

            $class = $entity_manager->getClassMetadata($class_name);
            if ($class) {
                $all_mappings = array();
                foreach($class->fieldMappings as $mapname => $mapping) {
                    $all_mappings[$mapname] = $mapping;
                }

                foreach($all_mappings as $mapname => $mapping) {
                    $add_field = false;
                    if (!isset($mapping['targetEntity'])) {
                        switch ($mapping['type']) {
                            case 'datetime':
                            case 'boolean':
                            case 'text':
                            case 'integer':
                                break;

                            case 'string':
                                if ($mapname !== 'passwd') {
                                    $add_field = true;
                                }
                                break;

                            default:
                                switch ($mapname) {
                                    case 'passwd':
                                        break;

                                    default:
                                        $add_field = true;
                                        break;
                                }
                                break;
                        }
                    }

                    if ($add_field) {
                        $fields[] = $mapname;
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Build CRUD form
     *
     * Guesses fields and checks for lots of possible overrides
     */
    protected function getForm($em, $item, $entity_name)
    {
        $form_options = array();

        $form_items = array();
        if ($em) {
            $class = $em->getClassMetadata(get_class($item));
            if ($class) {
                $all_mappings = array();
                foreach($class->fieldMappings as $mapname => $mapping) {
                    $all_mappings[$mapname] = $mapping;
                }

                //echo '<pre>'; var_dump($class->fieldMappings); echo '</pre>';
                //echo '<pre>'; var_dump($class->associationMappings); echo '</pre>';
                foreach($class->associationMappings as $mapname => $mapping) {
                    $all_mappings[$mapname] = $mapping;
                }

                //echo '<pre>'; var_dump($all_mappings); echo '</pre>';

                foreach($all_mappings as $mapname => $mapping) {
                    if (isset($mapping['id']) && ($mapping['id'] === true)) {
                        // always ignore id field
                        continue;
                    }

                    $name = $mapname;
                    $type = null;
                    $args = array();

                    //$args['label'] = $mapname . ' ('.$mapping['type'].')';
                    $args['label'] = $this->get('pivotx.translations')->translate(mb_strtolower($entity_name).'.crud-form.'.mb_strtolower($mapname));

                    if (!isset($mapping['targetEntity'])) {
                        switch ($mapping['type']) {
                            case 'datetime':
                                $type = 'datetime';
                                $args['date_widget'] = 'single_text';
                                $args['time_widget'] = 'single_text';
                                break;

                            case 'boolean':
                                $type = 'checkbox';
                                $args['required'] = false;
                                break;

                            case 'integer':
                                $type = 'number';
                                $args['precision'] = 0;
                                break;

                            case 'string':
                                $type = 'text';
                                break;

                            case 'text':
                                $type = 'textarea';
                                break;

                            default:
                                /*
                                switch ($mapname) {
                                    case 'passwd':
                                        $type = 'repeated';
                                        $args['type'] = 'password';
                                        $args['first_name']  = $this->get('pivotx.translations')->translate(mb_strtolower($entity_name).'.form.edit.'.mb_strtolower($mapname)).'.first';
                                        $args['second_name'] = $this->get('pivotx.translations')->translate(mb_strtolower($entity_name).'.form.edit.'.mb_strtolower($mapname)).'.second';
                                        break;
                                }
                                break;
                                 */
                        }
                    }

                    if (method_exists($item,'getCrudConfiguration_'.$mapname)) {
                        $method = 'getCrudConfiguration_'.$mapname;
                        $configuration = $item->$method();

                        if (isset($configuration['type']) && ($configuration['type'] === false)) {
                            // ignore
                            continue;
                        }
                        if (isset($configuration['arguments'])) {
                            $args = array_merge($args, $configuration['arguments']);
                        }
                        if (isset($configuration['type'])) {
                            $type = $configuration['type'];

                            switch ($configuration['type']) {
                                case 'choice':
                                    $args['choices'] = $configuration['choices'];
                                    unset($args['precision']);
                                    break;
                            }
                        }
                        if (isset($configuration['setencoderfactory'])) {
                            $method = $configuration['setencoderfactory'];
                            $item->$method($this->container->get('security.encoder_factory'));
                        }
                    }

                    if (is_null($type) && isset($mapping['targetEntity'])) {
                        if ($mapping['type'] & ClassMetaDataInfo::TO_ONE) {
                            $type = 'entity';
                            $args['class'] = $mapping['targetEntity'];
                            $args['property'] = 'generic_title';
                            $args['empty_value'] = $this->get('pivotx.translations')->translate('form.choices.empty-value');
                            //$args['choices'] = array( '0' => 'kies' );
                        }
                    }

                    if (is_null($type)) {
                        continue;
                    }

                    switch ($type) {
                        case 'choice':
                            break;
                        default:
                            unset($args['choices']);
                            break;
                    }

                    if (!isset($args['attr'])) {
                        $args['attr'] = array();
                    }
                    if (!isset($args['attr']['widget_class'])) {
                        $args['attr']['widget_class'] = '';
                    }
                    $args['attr']['input-type'] = $type;
                    switch ($type) {
                        case '':
                        case 'text':
                        case 'textarea':
                        case 'email':
                            $args['attr']['widget_class'] = trim($args['attr']['widget_class'].' span12');
                            break;
                    }

                    $form_items[] = array(
                        'name' => $mapname,
                        'type' => $type,
                        'options' => $args
                    );
                }
            }
        }

        $builder = $this->createFormBuilder($item, $form_options);
        $builder->setCompound(true);

        // @todo doesn't work
        //$builder->setAttribute('autocomplete', 'off');

        foreach($form_items as $form_item) {
            $builder->add($form_item['name'], $form_item['type'], $form_item['options']);
        }
        $form = $builder->getForm();

        return $form;
    }

    protected function getCrudTableArgumentsFromRequest(Request $request)
    {
        $args = array();
        foreach($request->query->all() as $name => $value) {
            if ((substr($name,0,7) == 'filter-') || (substr($name,0,6) == 'table-')) {
                $args[$name] = $value;
            }
        }

        return $args;
    }

    protected function getCrudSelection(Request $request)
    {
        $selection = array();
        if ($request->query->has('table-selection')) {
            $raw = $request->query->get('table-selection');
            foreach(explode(';', $raw) as $id) {
                if (intval($id) > 0) {
                    $selection[] = intval($id);
                }
            }
        }

        return $selection;
    }

    protected function deleteMultiple($entity, $selection)
    {
        $entity_class = $this->getEntityClass($entity);
        $entity_manager = $this->get('doctrine')->getEntityManager();

        foreach($selection as $id) {
            if ($id > 0) {
                $item = $entity_manager->find($entity_class,$id);
                if ($item && ($item instanceof $entity_class) && ($item->getId() > 0)) {
                    $entity_manager->remove($item);
                }
            }
        }

        $entity_manager->flush();
    }

    /**
     * Determine which widgets to display
     */
    private function determineCrudTableWidgets($entity)
    {
        $widgets = array();

        if ($entity['name'] == 'GenericResource') {
            $widgets[] = 'CrudWidgets/GenericResourceGeneral.html.twig';
        }
        else {
            $widgets[] = 'CrudWidgets/General.html.twig';
        }
        $widgets[] = 'CrudWidgets/Selection.html.twig';
        $widgets[] = 'CrudWidgets/ExportImport.html.twig';

        return $widgets;
    }

    /**
     * Where to put the edit/delete buttons
     */
    private function determineButtonPosition($fields)
    {
        $done = false;

        // set show_buttons to false
        foreach($fields as &$field) {
            $field['show_buttons'] = false;
        }

        // method: preferred fields by name
        if (!$done) {
            $preferred_fields = array ( 'title', 'name', 'email' );
            foreach($fields as &$field) {
                if (isset($field['in_crud']) && $field['in_crud'] && in_array($field['name'], $preferred_fields)) {
                    $field['show_buttons'] = true;
                    $done = true;
                    break;
                }
            }
        }

        // method: first field
        if (!$done) {
            foreach($fields as &$field) {
                if (isset($field['in_crud']) && $field['in_crud']) {
                    $field['show_buttons'] = true;
                    $done = true;
                    break;
                }
            }
        }

        return $fields;
    }

    /**
     * Load the entity definition
     */
    private function loadEntity($name)
    {
        $views = $this->get('pivotx.views');

        $view  = $views->findView('Backend/findEntities');
        if (is_null($view)) {
            return null;
        }
        $view->setArguments(array('verbose' => false, 'name' => $name));

        return $view->getValue();
    }

    /**
     * Show a CRUD table
     */
    public function showTableAction(Request $request)
    {
        if ($request->query->has('action')) {
            $selection = $this->getCrudSelection($request);

            switch ($request->query->get('action')) {
                case 'edit-multiple':
                    if (count($selection) > 0) {
                        $id = array_shift($selection);
                        $args = array(
                            'table-selection' => implode(';', $selection)
                        );
                        $url = $this->get('pivotx.routing')->buildUrl('_table/'.$request->get('entity').'/'.$id, $args);
                        return $this->redirect($url);
                    }
                    break;

                case 'delete-multiple':
                    if (count($selection) > 0) {
                        $this->deleteMultiple($request->get('entity'), $selection);
                    }
                    $url = $this->get('pivotx.routing')->buildUrl('_table/'.$request->get('entity'));
                    return $this->redirect($url);
                    break;
            }

            $content = implode(' - ',$selection);

            return new \Symfony\Component\HttpFoundation\Response($content, 200);
        }


        $context = $this->getDefaultHtmlContext();


        if (!$this->get('security.context')->isGranted($context['crud']['entity']['roles']['read'])) {
            return $this->forwardByReference('_page/no_access');
        }


        // init the CRUD view

        $view = \PivotX\Component\Views\Views::loadView('Crud/'.$context['crud']['entity']['name'].'/findAll');
        if (($view === false) || ($view instanceof \PivotX\Component\Views\EmptyView)) {
            // custom view not found, use the default
            $view = \PivotX\Component\Views\Views::loadView($context['crud']['entity']['name'].'/findAll');
        }

        $view->setCurrentPage(1, 10);

        $arguments  = array();
        $query_args = array();
        $page_variable = 'table-'.$this->get('pivotx.translations')->translate('pagination.page-variable');
        foreach($request->query->all() as $name => $value) {
            if (substr($name,0,7) == 'filter-') {
                $arguments[substr($name,7)] = $value;
                $query_args[$name] = $value;
            }
            else if (substr($name,0,6) == 'table-') {
                $arguments[substr($name,6)] = $value;
                $query_args[$name] = $value;

                if ($name == $page_variable) {
                    $view->setCurrentPage($request->query->getInt($page_variable), 10);
                    unset($arguments[substr($name,6)]);
                }
            }
        }
        if (count($arguments) > 0) {
            $view->setArguments($arguments);
        }
        if (count($query_args) > 0) {
            $view->setQueryArguments($query_args);
        }

        $context['crud']['entity']['fields'] = $this->determineButtonPosition($context['crud']['entity']['fields']);
        $context['widgets']                  = $this->determineCrudTableWidgets($context['crud']['entity']);
        $context['view']                     = $view;

        $table_html = $this
            ->render(array(
                    'Crud/'.$context['crud']['entity']['name'].'.table.html.twig',
                    'Crud/any.table.html.twig'
                ), $context)
            ->getContent()
            ;

        $context['table'] = $table_html;

        return $this->render('Crud/table.html.twig', $context);
    }

    /**
     * Show a subtable CRUD table
     *
     * @todo we might not need this anymore
     */
    public function showSubTableAction(Request $request)
    {
        $entityref_class = $this->getEntityClass($request->get('entityref'));

        $view = \PivotX\Component\Views\Views::loadView('Crud/'.$crud['entity'].'/findAll');
        if ($view === false) {
            // custom view not found, use the default
            $view = \PivotX\Component\Views\Views::loadView($crud['entity'].'/findAll');
        }

        $view->setArguments(array(
            strtolower($request->get('entityref')) => $request->get('id')
        ));

        $em = $this->get('doctrine')->getEntityManager();
        $item = $em->find($entityref_class,$request->get('id'));

        $widgets = array(
            'CrudWidgets/CommentGeneral.html.twig',
            'CrudWidgets/CommentSelection.html.twig'
        );

        $context = $this->getDefaultHtmlContext();

        $context['entityref'] = $request->get('entityref');

        $context['widgets'] = $widgets;
        $context['view'] = $view;
        $context['item'] = $item;

        $table_html = $this
            ->render('TwoKingsEBikeBundle:Crud:'.$crud['entity'].'.subtable.html.twig', $context)
            ->getContent()
            ;

        $context['table'] = $table_html;

        return $this->render('Crud/table.html.twig', $context);
    }

    /**
     * Render a specific record form
     */
    public function showGetForm(Request $request, $form, $item)
    {
        $context = $this->getDefaultHtmlContext();

        if (!$this->get('security.context')->isGranted($context['crud']['entity']['roles']['update'])) {
            return $this->forwardByReference('_page/no_access');
        }

        $selection = false;
        $selection_next_href = null;
        if ($request->query->has('table-selection')) {
            $selection = $this->getCrudSelection($request);
            if (count($selection) > 0) {
                $id = array_shift($selection);
                $args = array();
                if (count($selection) > 0) {
                    $args['table-selection'] = implode(';', $selection);
                }
                $url = $this->get('pivotx.routing')->buildUrl('_table/'.$context['crud']['entity']['name'].'/'.$id, $args);

                $selection = true;
                $selection_next_href = $url;
            }
        }

        $crud_links = array();
        $crud_snippets = array();
        if (($this->get('security.context')->isGranted('ROLE_DEVELOPER')) && ($item->getId() > 0)) {
            $entity_name = strtolower($context['crud']['entity']['name']);
            $id          = $item->getId();

            // we just use this to test for any routes
            $url = $this->get('pivotx.routing')->buildUrl($entity_name.'/'.$id);

            if (!is_null($url)) {
                $routing_generator = new \PivotX\Doctrine\Generator\Routing($this->get('pivotx.siteoptions'), $this->get('pivotx.translations'));
                $languages = $routing_generator->getLanguagesForSite($this->getCurrentSite());

                foreach($languages as $language) {
                    $url = $this->get('pivotx.routing')->buildUrl('(&language='.$language['name'].')@'.$entity_name.'/'.$id);
                    $crud_links[] = $url;
                }

                if (method_exists($item, 'getSlug')) {
                    $crud_snippets[] = '{{ ref(\''.$entity_name.'/'.$item->getSlug().'\') }}';
                }

                $crud_snippets[] = '{{ ref(\''.$entity_name.'/'.$id.'\') }}';

            }
        }


        $context['crud']['id']                  = $request->get('id');
        $context['crud']['selection']           = $selection;
        $context['crud']['selection_next_href'] = $selection_next_href;
        $context['crud']['links']               = $crud_links;
        $context['crud']['snippets']            = $crud_snippets;

        $context['item'] = $item;
        $context['form'] = $form->createView();

        return $this->render(
            'Crud/record.html.twig',
            $context
        );
    }

    /**
     * Show a record form
     */
    public function showGetRecordAction(Request $request, $entity_manager, $item)
    {
        $repository = $this->get('doctrine')->getRepository(get_class($item));

        $this->get('pivotx.activity')
            ->editorialMessage(
                null,
                ( $item->getId() > 0 ) ?  'Starting editor for <em>:entity</em> with id <strong>:id</strong>' : 'Starting editor for new <em>:entity</em>',
                array(
                    'entity' => $request->get('entity'),
                    'id' => $item->getId()
                )
            )
            ->notImportant()
            ->log()
            ;

        $form = $this->getForm($entity_manager, $item, $request->get('entity'));

        return $this->showGetForm($request, $form, $item);
    }

    /**
     * Save a CRUD record
     */
    public function showPostOrPutRecordAction(Request $request, $entity_manager, $item)
    {
        $context = $this->getDefaultHtmlContext();

        // @todo we only check update and not create/delete
        if (!$this->get('security.context')->isGranted($context['crud']['entity']['roles']['update'])) {
            return $this->forwardByReference('_page/no_access');
        }

        $form = $this->getForm($entity_manager, $item, $request->get('entity'));

        $form->bindRequest($request);

        if ($form->isValid()) {
            /*
            $data = $form->getData();
            var_dump($data);
            exit();
            //*/

            //var_dump($item);

            if (method_exists($item, 'fixCrudBeforePersist')) {
                $item->fixCrudBeforePersist();
            }

            $entity_manager->persist($item);
            $entity_manager->flush();

            if (method_exists($item, 'fixCrudAfterPersist')) {
                $item->fixCrudAfterPersist();
            }

            $activity = $this->get('pivotx.activity');
            if ($activity) {
                $activity
                    ->editorialMessage(
                        null,
                        'Modified <em>:entity</em> with id <strong>:id</strong>.',
                        array(
                            'entity' => $request->get('entity'),
                            'id' => $item->getId()
                        )
                    )
                    ->notImportant()
                    ->log()
                    ;
            }

            $args = $this->getCrudTableArgumentsFromRequest($request);
            if (isset($args['table-selection'])) {
                if ($args['table-selection'] != '') {
                    $args['action'] = 'edit-multiple';
                }
                else {
                    unset($args['table-selection']);
                }
            }

            $redirect_entity = $request->get('entity');
            if (method_exists($item,'getCrudRootEntity')) {
                $redirect_entity = $item->getCrudRootEntity();
            }

            $url = $this->get('pivotx.routing')->buildUrl('_table/'.$redirect_entity, $args);

            if ($request->getMethod() == 'POST') {
                return $this->redirect($url);
            }

            return new \Symfony\Component\HttpFoundation\Response('', 204, array('X-Location' => $url));
        }

        return $this->showGetForm($request, $form, $item);
    }

    /**
     * Delete a specific record
     */
    public function showDeleteRecordAction(Request $request, $entity_manager, $item)
    {
        if ($item->getId() > 0) {
            $entity_manager->remove($item);
        }
        $entity_manager->flush();

        if ($request->getMethod() == 'GET') {
            $args = $this->getCrudTableArgumentsFromRequest($request);

            $url = $this->get('pivotx.routing')->buildUrl('_table/'.$request->get('entity'), $args);

            return $this->redirect($url);
        }

        $data = array(
            'code' => '200',
            'message' => 'Succesfully deleted.',
        );

        $content = json_encode($data);

        return new \Symfony\Component\HttpFoundation\Response($content, $data['code']);
    }

    /**
     * Show a single CRUD record form or perform some action on it
     */
    public function showRecordAction(Request $request)
    {
        $entity_class = $this->getEntityClass($request->get('entity'));
        $entity_manager = $this->get('doctrine')->getEntityManager();

        $item  = null;
        $items = null;
        if ($request->get('id') > 0) {
            $item = $entity_manager->find($entity_class,$request->get('id'));
        }
        else {
            $item = new $entity_class;

            if (method_exists($item,'initNewCrudRecord')) {
                $item->initNewCrudRecord();
            }
        }

        $intention = false;
        switch ($request->getMethod()) {
            case 'DELETE':
                $intention = 'DELETE';
                break;

            case 'PUT':
                $intention = 'PUT';
                break;

            case 'POST':
                $intention = 'PUT';
                break;

            case 'GET':
                $intention = 'GET';

                if ($request->query->has('action')) {
                    switch ($request->query->get('action')) {
                        case 'delete':
                            $intention = 'DELETE';
                            break;
                    }
                }
                break;
        }

        switch ($intention) {
            case 'DELETE':
                return $this->showDeleteRecordAction($request, $entity_manager, $item);
            case 'PUT':
                return $this->showPostOrPutRecordAction($request, $entity_manager, $item);
            case 'GET':
                return $this->showGetRecordAction($request, $entity_manager, $item);
        }
    }

    /**
     * Get suggested field values
     *
     * Returns a list of possible values when trying to enter a unique value.
     * Usually reserved for SLUGs
     */
    public function suggestFieldValueAction(Request $request)
    {
        $entity_class   = $this->getEntityClass($request->get('entity'));
        $entity_manager = $this->get('doctrine')->getEntityManager();
        $repository     = $this->get('doctrine')->getRepository($entity_class);

        $item = null;
        if ($request->get('id') > 0) {
            $item = $entity_manager->find($entity_class, $request->get('id'));

            $entity_manager->detach($item);
        }
        else {
            $item = new $entity_class;
        }

        $field_name = $request->get('field');
        $values     = $request->request->all();

        $suggestions = array();
        if ((count($values) == 2) && (isset($values['id'])) && (isset($values[$field_name]))) {
            // test slug suggestions

            $suggestion = \PivotX\Doctrine\Feature\Sluggable\Helpers::normalizeSlug($values[$field_name]);
            $counter    = 0;
            while (($counter < 1000) && (count($suggestions) < 2)) {
                if ($counter == 0) {
                    $try_value = $suggestion;
                }
                else {
                    $try_value = $suggestion . '-' . $counter;
                }
                $counter++;

                $q = $entity_manager
                    ->createQuery('select t from '.$entity_class.' t where t.'.$field_name.' = :value')
                    ->setParameter('value', $try_value)
                    ;

                $items = $q->getResult();
                if (count($items) == 0) {
                    $suggestions[] = $try_value;
                }
                else if (count($items) == 1) {
                    if ($items[0]->getId() == $values['id']) {
                        $suggestions[] = $try_value;
                    }
                }
            }
        }
        else {
            // generate suggestions

            foreach($values as $name => $value) {
                $method = 'set'.ucfirst($name);
                if (method_exists($item, $method)) {
                    $item->$method($value);
                }
            }

            $counter = 0;
            while (($counter < 1000) && (count($suggestions) < 2)) {
                $try_value = $item->getSlugSuggestion($counter++);

                $q = $entity_manager
                    ->createQuery('select t from '.$entity_class.' t where t.'.$field_name.' = :value')
                    ->setParameter('value', $try_value)
                    ;

                $items = $q->getResult();
                if (count($items) == 0) {
                    $suggestions[] = $try_value;
                }
            }
        }

        $content = json_encode($suggestions);

        return new \Symfony\Component\HttpFoundation\Response($content, 200);
    }
}
