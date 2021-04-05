<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\DBAL\Types;

use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Types\ConversionException;

/**
 */
trait UtcTypeTrait
{
    private static ?DateTimeZone $utc = null;

    /**
     * @return DateTimeZone
     */
    private static function getUTC(): DateTimeZone
    {
        if (!self::$utc) {
            self::$utc = new DateTimeZone('UTC');
        }

        return self::$utc;
    }

    /**
     * @param string|null $dateString
     * @param string $dateFormat
     *
     * @return DateTime|null
     *
     * @throws ConversionException
     */
    private function convertToDateTime(?string $dateString, string $dateFormat): ?DateTime
    {
        if (null === $dateString) {
            return null;
        }

        $converted = DateTime::createFromFormat($dateFormat, $dateString, self::getUTC());

        if (!$converted) {
            throw ConversionException::conversionFailed($dateString, $this->getName());
        }

        $errors = $converted::getLastErrors();

        return $errors['warning_count'] > 0 && (int) $converted->format('Y') < 0 ? null : $converted;
    }
}
