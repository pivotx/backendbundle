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

        $widgets = array();
        if ($crud['entity'] == 'GenericResource') {
            $widgets[] = 'CrudWidgets/GenericResourceGeneral.html.twig';
        }
        else {
            $widgets[] = 'CrudWidgets/General.html.twig';
        }
        $widgets[] = 'CrudWidgets/Selection.html.twig';
        $widgets[] = 'CrudWidgets/ExportImport.html.twig';

        $context = $this->getDefaultHtmlContext();

        $context['crud']    = $crud;
        $context['widgets'] = $widgets;
        $context['view']    = $view;

        $table_html = $this
            ->render(array(
                    'Crud/'.$crud['entity'].'.table.html.twig',
                    'Crud/any.table.html.twig'
                ), $context)
            ->getContent()
            ;

        $context['table'] = $table_html;

        return $this->render('Crud/table.html.twig', $context);
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
            'CrudWidgets/CommentGeneral.html.twig',
            'CrudWidgets/CommentSelection.html.twig'
        );

        $context = $this->getDefaultHtmlContext();

        $context['crud'] = $crud;
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

        $context = $this->getDefaultHtmlContext();
        $context['crud'] = $crud;
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
        $entity_class = $this->getEntityClass($request->get('entity'));
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

        // unsophisticated way to set the temporary fields
        foreach($request->request->all() as $name => $value) {
            $method = 'set'.ucfirst($name);
            if (method_exists($item, $method)) {
                $item->$method($value);
            }
        }

        $field_name = $request->get('field');

        $suggestions = array();

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

        $content = json_encode($suggestions);

        return new \Symfony\Component\HttpFoundation\Response($content, 200);
    }
}
