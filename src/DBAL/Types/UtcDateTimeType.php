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
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;

/**
 * "Forked" from https://github.com/adrenalinkin/doctrine-utc-date-time and implemented
 * requiresSQLCommentHint() to suppress deprecation warnings with young symfony doctrine dbal versions.
 *
 */
class UtcDateTimeType extends DateTimeType
{
    use UtcTypeTrait;

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($date, AbstractPlatform $platform): ?string
    {
        if (null === $date) {
            return null;
        }

        if ($date instanceof DateTime) {
            return parent::convertToDatabaseValue($date->setTimezone($this::getUTC()), $platform);
        }

        throw ConversionException::conversionFailedInvalidType($date, $this->getName(), ['null', 'DateTime']);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($date, AbstractPlatform $platform): ?DateTime
    {
        return $this->convertToDateTime($date, $platform->getDateTimeFormatString());
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
