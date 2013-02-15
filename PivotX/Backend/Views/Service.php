<?php

/**
 * This file is part of the PivotX Core bundle
 *
 * (c) Marcel Wouters / Two Kings <marcel@twokings.nl>
 */

namespace PivotX\Backend\Views;

use PivotX\Component\Views\Service as ViewsService;
use PivotX\Component\Views\ArrayView;
use PivotX\Component\Translations\Service as TranslationsService;
use PivotX\Component\Siteoptions\Service as SiteoptionsService;
use Doctrine\Bundle\DoctrineBundle\Registry;

/**
 * @author Marcel Wouters <marcel@twokings.nl>
 *
 * @api
 */
class Service
{
    private $kernel = false;
    private $pivotx_views = false;
    private $pivotx_siteoptions = false;
    private $doctrine_registry = false;
    private $translations_service = false;

    /**
     */
    public function __construct($kernel, ViewsService $pivotx_views, TranslationsService $translations_service, SiteoptionsService $siteoptions_service, Registry $doctrine_registry)
    {
        $this->kernel               = $kernel;
        $this->pivotx_views         = $pivotx_views;
        $this->siteoptions_service  = $siteoptions_service;
        $this->doctrine_registry    = $doctrine_registry;
        $this->translations_service = $translations_service;

        /*
        // @todo no longer needed?
        $view = new loadContentMenu($this->doctrine_registry, $this->translations_service, 'Backend/loadContentMenu');
        $this->pivotx_views->registerView($view);
        unset($view);
        //*/

        $view = new findEntities($this->doctrine_registry, $this->siteoptions_service, 'Backend/findEntities');
        $this->pivotx_views->registerView($view);
        unset($view);

        $view = new findEntities2($this->kernel, $this->doctrine_registry, $this->siteoptions_service, 'Backend/findEntities2');
        $this->pivotx_views->registerView($view);
        unset($view);

        $suggestions = new \PivotX\Doctrine\Generator\Suggestions();
        $types = $suggestions->getFieldTypes();
        $view = new ArrayView($types, 'Backend/findEntityFieldTypes', 'PivotX/Backend', 'Return all the entity field types', array('returnAll', 'Backend'));
        $this->pivotx_views->registerView($view);
        unset($view);

        $features = $suggestions->getFeatures();
        $view = new ArrayView($features, 'Backend/findEntityFeatures', 'PivotX/Backend', 'Return all the entity features', array('returnAll', 'Backend'));
        $this->pivotx_views->registerView($view);
        unset($view);

        $entities = $suggestions->getEntities();
        $view = new ArrayView($entities, 'Backend/findPresetEntities', 'PivotX/Backend', 'Return all the preset entities', array('returnAll', 'Backend'));
        $this->pivotx_views->registerView($view);
        unset($view);

        $view = new findBundles($this->kernel, 'Backend/findBundles');
        $this->pivotx_views->registerView($view);
        unset($view);

        $view = new findUpdatableComponents($this->kernel, 'Backend/findUpdatableComponents');
        $this->pivotx_views->registerView($view);
        unset($view);


        $_sites = explode("\n", $this->siteoptions_service->getValue('config.sites', '', 'all'));
        $sites  = array();
        foreach($_sites as $_site) {
            $sites[$_site] = $_site;
        }
        $view = new ArrayView($sites, 'Backend/findSites', 'PivotX/Backend', 'Return all defined sites', array('returnAll', 'Backend'));
        $this->pivotx_views->registerView($view);
        unset($view);
    }
}
