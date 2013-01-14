<?php
/**
 * Backend RelativeTime conversion
 *
 * Convert a time to a text string relative to 'now'
 *
 * @todo translation stuff should be included here
 */

namespace PivotX\Backend\Formats;

use \PivotX\Component\Formats\AbstractFormat;

class RelativeTimeFormat extends AbstractFormat
{
    public function format($in, $arguments = array())
    {
        $time = false;
        if (is_scalar($in) && is_numeric($in)) {
            $time = new \DateTime;
            $time->setTimestamp($in);
        }
        else if (is_scalar($in)) {
            $time = new \DateTime($in);
        }
        else if ($in instanceof \DateTime) {
            $time = $in;
        }

        if ($time === false) {
            return 'now';
        }

        $now  = time();
        $diff = $now - $time->getTimestamp();

        if ($diff == 0) {
            return 'now';
        }

        $postfix = 'ago';
        if ($diff < 0) {
            $diff    *= -1;
            $postfix  = 'in the future';
        }

        $seconds = $diff % 60;
        $minutes = floor($diff / 60) % 60;
        $hours   = floor($diff / 3600) % 24;
        $days    = floor($diff / 86400) % 3600;

        if ($days > 7) {
            $fmt = '%a %e %b %Y, %H:%M';
            return strftime($fmt, $time->getTimestamp());
        }

        $texts = array();
        if ($days > 0) {
            $texts[] = $days . ' day' . (($days == 1) ? '' : 's');
        }
        if ($hours > 0) {
            $texts[] = $hours . ' hour' . (($hours == 1) ? '' : 's');
        }
        if ($minutes > 0) {
            $texts[] = $minutes . ' minute' . (($minutes == 1) ? '' : 's');
        }
        if ($seconds > 0) {
            $texts[] = $seconds . ' second' . (($seconds == 1) ? '' : 's');
        }

        if (count($texts) > 2) {
            array_splice($texts, 2);
        }

        if (count($texts) == 2) {
            return $texts[0].' and '.$texts[1].' '.$postfix;
        }
        return $texts[0].' '.$postfix;
    }
}
