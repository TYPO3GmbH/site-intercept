<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Intercept\Utility;

class TimeUtility
{
    /**
     * Used to transform seconds given as integers
     *
     * @param int $seconds
     * @return string
     */
    public static function convertSecondsToHumanReadable(int $seconds) : string
    {
        $startTime = new \DateTime('@' . 0);
        $givenSeconds = new \DateTime('@' . $seconds);
        $format = '%Im %Ss';
        if ($seconds > 3600) {
            $format = '%hh %Im %Ss';
        }
        if ($seconds <= 60) {
            $format = '%Ss';
        }
        return $startTime->diff($givenSeconds)->format($format);
    }
}
