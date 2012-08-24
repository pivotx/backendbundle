<?php
/**
 * Backend auto-formatting conversion in this file.
 *
 * Normally you would want proper classes per kind of formatting.
 */

namespace PivotX\Backend\Formats;

use \PivotX\Component\Formats\AbstractFormat;

class AutoFormat extends AbstractFormat
{
    public function format($in, $arguments = array())
    {
        if (is_null($in)) {
            return '-';
        }

        if (is_object($in)) {
            if (method_exists($in, '__toString')) {
                return $in->__toString();
            }

            switch (get_class($in)) {
                case 'DateTime':
                    //return $in->format(\DateTime::RFC3339);
                    return $in->format('Y-m-d H:i:s');
                    break;

                default:
                    return 'object class:'.get_class($in);
            }
        }

        return 'Unknown source';
    }
}
