<?php

namespace PivotX\Backend\Lists;

use PivotX\Component\Lists\Item;
use PivotX\Component\Lists\RouteItem;
use PivotX\Component\Lists\SeparatorItem;

class Siteadmin extends Item
{
    public function __construct()
    {
        parent::__construct('site-administration');

        $this->setAttribute('icon', 'icon-fire');
        $this->resetBreadcrumb();

        $menu = $this->addItem(new Item('status'));
        $menu = $this->addItem(new Item('configuration'));
        $menu = $this->addItem(new Item('entities'));
        $menu->resetBreadcrumb();
        $submenu = $menu->addItem(new RouteItem('entry', '_siteadmin/entity/entry'));
        $submenu = $menu->addItem(new RouteItem('page', '_siteadmin/entity/page'));

        /*
        $menu = $this->addItem(new Item('tables'));
        $crudmenu = $menu->addItem(new CrudTables('siteadmin'));
        $crudmenu->setAsItemsholder();
        //*/
        //*
        $this->addItem(new SeparatorItem());
        $crudmenu = $this->addItem(new CrudTables('siteadmin'));
        $crudmenu->setAsItemsholder();
        //*/
    }
}
