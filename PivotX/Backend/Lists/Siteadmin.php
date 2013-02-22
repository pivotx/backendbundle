<?php

namespace PivotX\Backend\Lists;

use PivotX\Component\Lists\Item;
use PivotX\Component\Lists\RouteItem;
use PivotX\Component\Lists\UrlItem;
use PivotX\Component\Lists\SeparatorItem;

class Siteadmin extends Item
{
    public function __construct()
    {
        parent::__construct('site-administration');

        $this->setRole('ROLE_ADMIN');
        $this->setAttribute('icon', 'icon-wrench');
        $this->resetBreadcrumb();

        $menu = $this->addItem(new RouteItem('status', '_siteadmin/status'));
        //$menu = $this->addItem(new UrlItem('configuration', '#'));
        //$menu->resetBreadcrumb();
        //$submenu = $menu->addItem(new RouteItem('entry', '_siteadmin/entity/entry'));
        //$submenu = $menu->addItem(new RouteItem('page', '_siteadmin/entity/page'));

        $this->addItem(new SeparatorItem());
        $crudmenu = $this->addItem(new CrudTables('siteadmin'));
        $crudmenu->setAsItemsholder();
    }
}
