<?php
/**
 * Backend auto-formatting conversion
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
                    $fmt = '%a %e %b %Y, %H:%M';
                    if (count($arguments) >= 1) {
                        switch ($arguments[0]) {
                            case 'readable':
                                $fmt = '%a %e %b %Y, %H:%M';
                                break;
                            case 'technical':
                                $fmt = '%F %T';
                                break;
                        }
                    }
                    return strftime($fmt, $in->getTimestamp());
                    break;

                case 'Doctrine\ORM\PersistentCollection':
                    if (count($in) == 0) {
                        return '#0';
                    }
                    return '#'.count($in);
                    break;

                default:
                    return 'object class:'.get_class($in);
            }
        }
        else if (is_scalar($in)) {
            return $in;
        }

        return 'Unknown source';
    }
}
