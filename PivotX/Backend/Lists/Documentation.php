<?php

namespace PivotX\Backend\Lists;

use PivotX\Component\Lists\Item;
use PivotX\Component\Lists\RouteItem;
use PivotX\Component\Lists\SeparatorItem;

class Documentation extends Item
{
    public static function getPath()
    {
        // @todo faulty logic
        return dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/CoreBundle/Resources/doc';
    }

    public function __construct()
    {
        parent::__construct('documentation');

        $this->setAttribute('icon', 'icon-book');
        $this->setRole('ROLE_EDITOR');

        $directory = self::getPath();
        $files     = scandir($directory);
        foreach($files as $filename) {
            $file = $directory . '/' . $filename;

            if (is_file($file)) {
                $path_parts = pathinfo($filename);

                $title    = ucfirst(str_replace('_', ' ', $path_parts['filename']));
                $basename = $path_parts['filename'];

                $this->addItem(new RouteItem($title, '_documentation/'.$basename));

            }
        }
    }
}
