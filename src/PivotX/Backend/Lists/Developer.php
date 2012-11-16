<?php

namespace PivotX\Backend\Lists;

use PivotX\Component\Lists\Item;
use PivotX\Component\Lists\RouteItem;

class Developer extends Item
{
    public function __construct()
    {
        parent::__construct('development');

        $this->setAttribute('icon', 'icon-fire');
        $this->resetBreadcrumb();

        $this->addItem(new RouteItem('webresourcer', '_developer/webresourcer'));
        $this->addItem(new RouteItem('routing', '_developer/routing'));
        $this->addItem(new RouteItem('views', '_developer/views'));
        $this->addItem(new RouteItem('formats', '_developer/formats'));
    }
}
