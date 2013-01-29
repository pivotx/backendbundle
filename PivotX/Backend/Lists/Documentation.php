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
        return dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/corebundle/Resources/doc';
    }

    public function __construct()
    {
        parent::__construct('documentation');

        $this->setAttribute('icon', 'icon-book');
        $this->setRole('ROLE_EDITOR');

        $auto_order_number = 1000;

        $directory = self::getPath();
        $files     = scandir($directory);
        $pages     = array();
        foreach($files as $filename) {
            $file = $directory . '/' . $filename;

            if (is_file($file)) {
                $path_parts = pathinfo($filename);

                $title    = ucfirst(str_replace('_', ' ', $path_parts['filename']));
                $basename = $path_parts['filename'];

                $order_number = $auto_order_number++;

                $head_contents = file_get_contents($file, false, null, 0, 1024);
                if (preg_match('|<!-- 0*([0-9]+) (.+)|', $head_contents, $match)) {
                    $order_number = $match[1];
                    $title        = trim($match[2]);
                }

                $pages[$order_number] = array($title, $basename);

            }
        }

        ksort($pages, SORT_NUMERIC);

        foreach($pages as $number => $page) {
            $title    = $page[0];
            $basename = $page[1];

            $this->addItem(new RouteItem($title, '_documentation/'.$basename));
        }
    }
}
