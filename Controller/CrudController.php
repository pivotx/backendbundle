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
    protected function getEntityClass($name)
    {
        $ems = $this->container->get('doctrine')->getEntityManagers();
        foreach ($ems as $em) {
            $classes = $em->getMetadataFactory()->getAllMetadata();
            foreach($classes as $class) {
                //echo "Class: ".$class->name."\n";
                //var_dump($class);

                $_p = explode('\\',$class->name);
                $base_class = $_p[count($_p)-1];

                if ($base_class == $name) {
                    return $class->name;
                }

                //var_dump($paths,$base_class);
                //echo 'Base-class: '.$base_class."\n";
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
                $ignore_mapnames = array();
                if (method_exists($item,'getCrudFormIgnores')) {
                    $ignore_mapnames = $item->getCrudFormIgnores();
                }

                $all_mappings = array();
                foreach($class->fieldMappings as $mapname => $mapping) {
                    $all_mappings[$mapname] = $mapping;
                }

                foreach($all_mappings as $mapname => $mapping) {
                    if (in_array($mapname, $ignore_mapnames)) {
                        // ignore these
                        continue;
                    }

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
                $ignore_mapnames = array();
                if (method_exists($item,'getCrudFormIgnores')) {
                    $ignore_mapnames = $item->getCrudFormIgnores();
                }

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

                    // @todo temporary thing
                    if (in_array($mapname, array('date_created', 'date_modified'))) {
                        // always ignore these fields
                        continue;
                    }
                    if (in_array($mapname, $ignore_mapnames)) {
                        // ignore these
                        continue;
                    }

                    $name = $mapname;
                    $type = null;
                    $args = array();

                    //$args['label'] = $mapname . ' ('.$mapping['type'].')';
                    $args['label'] = $this->get('pivotx.translations')->translate(mb_strtolower($entity_name).'.form.edit.'.mb_strtolower($mapname));

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
                                switch ($mapname) {
                                    case 'passwd':
                                        $type = 'repeated';
                                        $args['type'] = 'password';
                                        $args['first_name']  = $this->get('pivotx.translations')->translate(mb_strtolower($entity_name).'.form.edit.'.mb_strtolower($mapname)).'.first';
                                        $args['second_name'] = $this->get('pivotx.translations')->translate(mb_strtolower($entity_name).'.form.edit.'.mb_strtolower($mapname)).'.second';
                                        break;
                                }
                                break;
                        }
                    }

                    // if we encounter this method and it returns true, ignore this field in the crud
                    if (method_exists($item,'getCrudFormIgnore_'.$mapname)) {
                        $method = 'getCrudFormIgnore_'.$mapname;
                        if ($item->$method()) {
                            continue;
                        }
                    }
                    // if we encounter this method, overwrite arguments
                    if (method_exists($item,'getCrudFormArguments_'.$mapname)) {
                        $method         = 'getCrudFormArguments_'.$mapname;
                        $crud_arguments = $item->$method();
                        $args           = array_merge($args,$crud_arguments);
                    }
                    // if we encounter this method, change type to 'choice' and fill in the options
                    if (method_exists($item,'getCrudFormChoices_'.$mapname)) {
                        $method          = 'getCrudFormChoices_'.$mapname;
                        $choices         = $item->$method();
                        $type            = 'choice';
                        $args['choices'] = $choices;
                        unset($args['precision']);
                        /*
                        foreach($choices as $choice) {
                            $label = $choice;
                            $args['choices'][$choice] = $label;
                        }
                        */
                    }
                    // only type gets overwritten
                    if (method_exists($item,'getCrudFormType_'.$mapname)) {
                        $method = 'getCrudFormType_'.$mapname;
                        $type   = $item->$method();
                    }

                    if (is_null($type) && isset($mapping['targetEntity'])) {
                        if ($mapping['type'] & ClassMetaDataInfo::TO_ONE) {
                            $type = 'entity';
                            $args['class'] = $mapping['targetEntity'];
                            $args['property'] = 'title';
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

                    // @todo temporary thing?
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
     * Show a CRUD table
     */
    public function showTableAction(Request $request)
    {
        $html = array(
            'language' => 'en',
            'meta' => array(
                'charset' => 'utf-8',
            ),
            'title' => 'PivotX back-end'
        );

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

        $crud = array(
            'htmlid' => 'id'.rand(100,999),
            'entity' => $request->get('entity'),
            'fields' => $this->getEntityFields($this->getEntityClass($request->get('entity')))
        );

        $view = \PivotX\Component\Views\Views::loadView('Crud/'.$crud['entity'].'/findAll');
        if (($view === false) || ($view instanceof \PivotX\Component\Views\EmptyView)) {
            // custom view not found, use the default
            $view = \PivotX\Component\Views\Views::loadView($crud['entity'].'/findAll');
        }

        $view->setCurrentPage(1, 10);

        $arguments  = array();
        $query_args = array();
        $page_variable = 'table-'.$this->get('pivotx.translations')->translate('pagination.page_variable');
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

        $widgets = array();
        if ($crud['entity'] == 'GenericResource') {
            $widgets[] = 'BackendBundle:CrudWidgets:GenericResourceGeneral.html.twig';
        }
        else {
            $widgets[] = 'BackendBundle:CrudWidgets:General.html.twig';
        }
        $widgets[] = 'BackendBundle:CrudWidgets:Selection.html.twig';
        $widgets[] = 'BackendBundle:CrudWidgets:ExportImport.html.twig';

        $context = array(
            'html' => $html,
            'crud' => $crud,
            'widgets' => $widgets,
            'view' => $view
        );

        // @todo should not be hard-wired here of course
        $table_html = $this
            ->render(array(
                    'TwoKingsEBikeBundle:Crud:'.$crud['entity'].'.table.html.twig',
                    'BackendBundle:Crud:'.$crud['entity'].'.table.html.twig',
                    'BackendBundle:Crud:any.table.html.twig'
                ), $context)
            ->getContent()
            ;

        $context['table'] = $table_html;

        return $this->render('BackendBundle:Crud:table.html.twig', $context);
    }

    /**
     * Show a subtable CRUD table
     */
    public function showSubTableAction(Request $request)
    {
        $crud = array(
            'htmlid' => 'id'.rand(100,999),
            'entity' => $request->get('entity'),
            'entityref' => $request->get('entityref'),
        );
        $entityref_class = $this->getEntityClass($crud['entityref']);

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
            'BackendBundle:CrudWidgets:CommentGeneral.html.twig',
            'BackendBundle:CrudWidgets:CommentSelection.html.twig'
        );

        $context = array(
            'crud' => $crud,
            'widgets' => $widgets,
            'view' => $view,
            'item' => $item
        );

        $table_html = $this
            ->render('TwoKingsEBikeBundle:Crud:'.$crud['entity'].'.subtable.html.twig', $context)
            ->getContent()
            ;

        $context['table'] = $table_html;

        return $this->render('BackendBundle:Crud:table.html.twig', $context);
    }

    /**
     * Render a specific record form
     */
    public function showGetForm(Request $request, $form, $item)
    {
        $html = array(
            'language' => 'en',
            'meta' => array(
                'charset' => 'utf-8',
            ),
            'title' => 'PivotX back-end'
        );

        $crud = array(
            'entity' => $request->get('entity'),
            'id' => $request->get('id'),
            'selection' => false
        );

        if ($request->query->has('table-selection')) {
            $selection = $this->getCrudSelection($request);
            if (count($selection) > 0) {
                $id = array_shift($selection);
                $args = array();
                if (count($selection) > 0) {
                    $args['table-selection'] = implode(';', $selection);
                }
                $url = $this->get('pivotx.routing')->buildUrl('_table/'.$request->get('entity').'/'.$id, $args);

                $crud['selection'] = true;
                $crud['selection_next_href'] = $url;
            }
        }

        return $this->render(
            'BackendBundle:Crud:record.html.twig',
            array('html' => $html, 'crud' => $crud, 'item' => $item, 'form' => $form->createView())
        );
    }

    /**
     * Show a record form
     */
    public function showGetRecordAction(Request $request, $entity_manager, $item)
    {
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
        $form = $this->getForm($entity_manager, $item, $request->get('entity'));

        $form->bindRequest($request);

        if ($form->isValid()) {
            /*
            $data = $form->getData();
            var_dump($data);
            //*/

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
     * For instance for 'publicid' or 'uri' fields.
     */
    public function suggestFieldValueAction(Request $request)
    {
        $entity_class = $this->getEntityClass($request->get('entity'));
        $entity_manager = $this->get('doctrine')->getEntityManager();
        $repository     = $this->get('doctrine')->getRepository($entity_class);


        $unique_value = '';
        foreach($request->request->all() as $name => $value) {
            $unique_value .= ' ' . $value;
        }
        $unique_value = preg_replace('|[^a-z0-9]+|', '-', mb_strtolower(trim($unique_value)));
        $unique_value = preg_replace('|^-*(.+?)-*$|', '\\1', $unique_value);

        $field_name = $request->get('field');

        $suggestions = array();

        $base_value = $unique_value;
        $counter    = 0;
        if (preg_match('|(.+)-([0-9]+)$|', $base_value, $match)) {
            $base_value = $match[1];
            $counter    = intval($match[2]) + 1;
        }
        do {
            $try_value = $base_value;
            if ($counter > 0) {
                $try_value .= '-' . $counter;
            }

            $q = $entity_manager
                ->createQuery('select t from '.$entity_class.' t where t.'.$field_name.' = :value')
                ->setParameter('value', $try_value)
                ;

            $items = $q->getResult();
            if (count($items) == 0) {
                $suggestions[] = $try_value;
            }
            $counter++;
        }
        while ((count($suggestions) < 2) && ($counter < 1000));

        $content = json_encode($suggestions);

        return new \Symfony\Component\HttpFoundation\Response($content, 200);
    }
}
