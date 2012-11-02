<?php

/**
 * This file is part of the PivotX Core bundle
 *
 * (c) Marcel Wouters / Two Kings <marcel@twokings.nl>
 */

namespace PivotX\Backend\Views;

use PivotX\Component\Views\Service as ViewsService;
use PivotX\Component\Translations\Service as TranslationsService;
use Doctrine\Bundle\DoctrineBundle\Registry;

/**
 * @author Marcel Wouters <marcel@twokings.nl>
 *
 * @api
 */
class Service
{
    private $pivotx_views = false;
    private $doctrine_registry = false;
    private $translations_service = false;

    /**
     */
    public function __construct(ViewsService $pivotx_views, TranslationsService $translations_service, Registry $doctrine_registry)
    {
        $this->pivotx_views         = $pivotx_views;
        $this->doctrine_registry    = $doctrine_registry;
        $this->translations_service = $translations_service;

        $view = new loadContentMenu($this->doctrine_registry, $this->translations_service, 'Backend/loadContentMenu');
        $this->pivotx_views->registerView($view);
        unset($view);
    }
}
