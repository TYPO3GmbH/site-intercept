<?php
declare(strict_types = 1);
namespace T3G\Intercept\Utility;

class TimeUtility
{
    /**
     * Used to transform seconds given as integers
     *
     * @param int $seconds
     *
     * @return string
     */
    static public function convertSecondsToHumanReadable(int $seconds) : string
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
