<?php

namespace PivotX\BackendBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BackendBundle extends Bundle
{
    public function boot()
    {
        try {
            $service = $this->container->get('pivotx.routing');

            echo "HIER<br/>\n";

            $fname = dirname(dirname(dirname(__FILE__))).'/Resources/config/pivotxrouting.yml';
            echo 'filename['.$fname.']<br/>';
            die('');
            $service->load($fname);
        }
        catch (\InvalidArgumentException $e) {
        }
        echo 'hier wel';

        // force loading of our views
        // @todo this is not pretty
        $views = $this->container->get('pivotx.backend.views');

        // force loading of our formats
        // @todo this is not pretty
        $formats = $this->container->get('pivotx.backend.formats');
    }
}
