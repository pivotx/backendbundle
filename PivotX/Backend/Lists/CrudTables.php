<?php

namespace PivotX\Backend\Lists;

use PivotX\Component\Lists\Item;
use PivotX\Component\Lists\RouteItem;

class CrudTables extends Item
{
    public function __construct($kind)
    {
        parent::__construct('crud-tables');

        //$this->setAttribute('icon', 'icon-th-list');
        $this->resetBreadcrumb();


        // @todo should not be hard-coded

        switch ($kind) {
            case 'editor':
                $menu = $this->addItem(new \PivotX\Component\Lists\RouteItem('resources', '_table/GenericResource'));
                $submenu = $menu->addItem(new \PivotX\Component\Lists\RouteItem('genericresource', '_table/GenericResource/{id}'));
                $submenu->resetInMenu();
                break;

            case 'siteadmin':
                $menu = $this->addItem(new \PivotX\Component\Lists\RouteItem('users', '_table/User'));
                $submenu = $menu->addItem(new \PivotX\Component\Lists\RouteItem('user', '_table/User/{id}'));
                $submenu->resetInMenu();
                $menu = $this->addItem(new \PivotX\Component\Lists\RouteItem('activitylog', '_table/ActivityLog'));
                $submenu = $menu->addItem(new \PivotX\Component\Lists\RouteItem('activitylog', '_table/ActivityLog/{id}'));
                $submenu->resetInMenu();
                break;

            case 'developer':
                $menu = $this->addItem(new \PivotX\Component\Lists\RouteItem('siteoptions', '_table/SiteOption'));
                $submenu = $menu->addItem(new \PivotX\Component\Lists\RouteItem('siteoption', '_table/SiteOption/{id}'));
                $submenu->resetInMenu();
                $menu = $this->addItem(new \PivotX\Component\Lists\RouteItem('translations', '_table/TranslationText'));
                $submenu = $menu->addItem(new \PivotX\Component\Lists\RouteItem('translation', '_table/TranslationText/{id}'));
                $submenu->resetInMenu();
                break;
        }
    }
}
