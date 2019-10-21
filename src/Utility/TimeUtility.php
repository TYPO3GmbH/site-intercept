<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Utility;

use DateTime;
use DateTimeZone;

/**
 * Helper class for time formatting
 */
class TimeUtility
{
    /**
     * Used to transform seconds given as integers to something readable.
     *
     * @param int $seconds
     * @return string
     */
    public static function convertSecondsToHumanReadable(int $seconds): string
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

    /**
     * @return array
     */
    public static function timeZones(): array
    {
        static $regions = [
            DateTimeZone::AFRICA,
            DateTimeZone::AMERICA,
            DateTimeZone::ANTARCTICA,
            DateTimeZone::ASIA,
            DateTimeZone::ATLANTIC,
            DateTimeZone::AUSTRALIA,
            DateTimeZone::EUROPE,
            DateTimeZone::INDIAN,
            DateTimeZone::PACIFIC,
            DateTimeZone::UTC,
        ];

        $timezones = [];
        foreach ($regions as $region) {
            $timezones = array_merge($timezones, DateTimeZone::listIdentifiers($region));
        }

        $timezoneOffsets = [];
        foreach ($timezones as $timezone) {
            $timezoneOffsets[$timezone] = (new DateTimeZone($timezone))->getOffset(new DateTime());
        }

        // sort timezone by offset
        asort($timezoneOffsets);

        $timezoneList = [];
        foreach ($timezoneOffsets as $timezone => $offset) {
            $offsetPrefix    = $offset < 0 ? '-' : '+';
            $offsetFormatted = gmdate('H:i', abs($offset));

            $offsetPretty = 'UTC' . $offsetPrefix . $offsetFormatted;

            $timezoneList[$timezone] = '(' . $offsetPretty . ') ' . $timezone;
        }

        return $timezoneList;
    }
}
