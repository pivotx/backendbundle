<?php

namespace PivotX\Backend\Lists;

use PivotX\Component\Lists\Item;
use PivotX\Component\Lists\RouteItem;
use PivotX\Component\Lists\SeparatorItem;

class Developer extends Item
{
    public function __construct()
    {
        parent::__construct('development');

        $this->setAttribute('icon', 'icon-globe');
        $this->resetBreadcrumb();

        $this->addItem(new RouteItem('entities', '_entities/all'));

        $this->addItem(new RouteItem('webresourcer', '_developer/webresourcer'));
        $this->addItem(new RouteItem('routing', '_developer/routing'));
        $this->addItem(new RouteItem('views', '_developer/views'));
        $this->addItem(new RouteItem('formats', '_developer/formats'));

        /*
        $tablesmenu = $this->addItem(new Item('tables'));
        $crudmenu = $tablesmenu->addItem(new CrudTables('developer'));
        $crudmenu->setAsItemsholder();
*/

        $this->addItem(new SeparatorItem());
        $crudmenu = $this->addItem(new CrudTables('developer'));
        $crudmenu->setAsItemsholder();
    }
}
