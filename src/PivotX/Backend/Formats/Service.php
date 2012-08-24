<?php

/**
 * This file is part of the PivotX Backend bundle
 *
 * (c) Marcel Wouters / Two Kings <marcel@twokings.nl>
 */

namespace PivotX\Backend\Formats;

use PivotX\Component\Formats\Service as FormatsService;

/**
 * @author Marcel Wouters <marcel@twokings.nl>
 *
 * @api
 */
class Service
{
    protected $pivotx_views = false;
    protected $doctrine_registry = false;

    /**
     */
    public function __construct(FormatsService $pivotx_formats)
    {
        $this->pivotx_formats = $pivotx_formats;

        $format = new AutoFormat('Backend/Auto', 'PivotX/Backend', 'Convert any source to some string format.');
        $this->pivotx_formats->registerFormat($format);
    }
}
