<?php

/**
 * This file is part of the PivotX Core bundle
 *
 * (c) Marcel Wouters / Two Kings <marcel@twokings.nl>
 */

namespace PivotX\Backend\Views;

use PivotX\Component\Views\Service as ViewsService;
use Doctrine\Bundle\DoctrineBundle\Registry;

/**
 * @author Marcel Wouters <marcel@twokings.nl>
 *
 * @api
 */
class Service
{
    protected $pivotx_views = false;
    protected $doctrine_registry = false;

    /**
     */
    public function __construct(ViewsService $pivotx_views, Registry $doctrine_registry)
    {
        $this->pivotx_views      = $pivotx_views;
        $this->doctrine_registry = $doctrine_registry;

        $view = new loadContentMenu($this->doctrine_registry, 'Backend/loadContentMenu');
        $this->pivotx_views->registerView($view);
        unset($view);
    }
}
