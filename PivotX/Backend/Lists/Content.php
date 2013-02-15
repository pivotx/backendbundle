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

        $er = new \PivotX\Doctrine\Generator\EntitiesRepresentation();
        $er->importPivotConfiguration($siteoptions_service);
        $entities = $er->getEntities();
        foreach($entities as $entity) {
            $name          = $entity->getName();
            $internal_name = $entity->getInternalName();
            $pluralname    = \PivotX\Component\Translations\Inflector::pluralize($internal_name);

            $menu = $this->addItem(new \PivotX\Component\Lists\RouteItem($pluralname, '_table/'.$name));
            $submenu = $menu->addItem(new \PivotX\Component\Lists\RouteItem($name, '_table/'.$name.'/{id}'));
            $submenu->resetInMenu();
        }
    }
}
