<?php

namespace PivotX\Backend\Lists;

use PivotX\Component\Lists\Item;
use PivotX\Component\Lists\RouteItem;
use PivotX\Component\Lists\SeparatorItem;

class Content extends Item
{
    public function __construct($siteoptions_service)
    {
        parent::__construct('content');

        $this->setRole('ROLE_EDITOR');
        $this->setAttribute('icon', 'icon-pencil');
        $this->resetBreadcrumb();

        /*
        $contentmenu = $this->addItem(new \PivotX\Component\Lists\Item('content'));
        $contentmenu->setAttribute('icon', 'icon-pencil');
        $contentmenu->resetBreadcrumb();
         */

        //$this->addItem(new SeparatorItem());

        $crudmenu = $this->addItem(new CrudTables('editor'));
        $crudmenu->setAsItemsholder();

        $entities = $siteoptions_service->getValue('config.entities', array(), 'all');
        foreach($entities as $entity) {
            // @todo fixed entities should not be in this menu

            $name = strtolower($entity);
            $pluralname = \PivotX\Component\Translations\Inflector::pluralize($name);

            $menu = $this->addItem(new \PivotX\Component\Lists\RouteItem($pluralname, '_table/'.$entity));
            $submenu = $menu->addItem(new \PivotX\Component\Lists\RouteItem($name, '_table/'.$entity.'/{id}'));
            $submenu->resetInMenu();
        }
    }
}
