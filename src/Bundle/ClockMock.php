<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Bundle;

/**
 * Class for use in tests to mock the current system time.
 * This is heavily based on https://github.com/symfony/phpunit-bridge/blob/master/ClockMock.php
 *
 * @codeCoverageIgnore Not testing testing related classes.
 */
class ClockMock
{
    private static $now;

    /**
     * @param null|int|float $enable
     */
    public static function withClockMock($enable = null): void
    {
        if (!is_numeric($enable) && !$enable) {
            self::$now = null;
            return;
        }
        self::$now = is_numeric($enable) ? (float)$enable : microtime(true);
    }

    /**
     * @return int
     */
    public static function time(): int
    {
        if (self::$now === null) {
            return \time();
        }
        return (int)self::$now;
    }

    /**
     * @param int $s
     * @return int
     */
    public static function sleep(int $s): int
    {
        if (self::$now === null) {
            return \sleep($s);
        }
        self::$now += $s;
        return 0;
    }

    /**
     * @param int $us
     */
    public static function usleep(int $us): void
    {
        if (self::$now === null) {
            \usleep($us);
            return;
        }
        self::$now += $us / 1000000;
    }

    /**
     * @param bool $asFloat
     * @return float|string
     */
    public static function microtime(bool $asFloat = false)
    {
        if (self::$now === null) {
            return \microtime($asFloat);
        }
        if ($asFloat) {
            return self::$now;
        }
        return sprintf('%0.6f00 %d', self::$now - (int)self::$now, (int)self::$now);
    }

    /**
     * @param string $format
     * @param null $timestamp
     * @return string
     */
    public static function date(string $format, $timestamp = null): string
    {
        if ($timestamp === null) {
            $timestamp = self::time();
        }
        return \date($format, $timestamp);
    }

    /**
     * Registers the mock for the given class
     *
     * @param string $class
     */
    public static function register(string $class): void
    {
        $self = static::class;
        $mockedNs = [substr($class, 0, strrpos($class, '\\'))];
        if (0 < strpos($class, '\\Tests\\')) {
            $ns = str_replace('\\Tests\\', '\\', $class);
            $mockedNs[] = substr($ns, 0, strrpos($ns, '\\'));
        } elseif (0 === strpos($class, 'Tests\\')) {
            $mockedNs[] = substr($class, 6, strrpos($class, '\\') - 6);
        }
        foreach ($mockedNs as $ns) {
            if (\function_exists($ns . '\time')) {
                continue;
            }
            eval(<<<EOPHP
namespace $ns;
function time()
{
    return \\$self::time();
}
function microtime(\$asFloat = false)
{
    return \\$self::microtime(\$asFloat);
}
function sleep(\$s)
{
    return \\$self::sleep(\$s);
}
function usleep(\$us)
{
    return \\$self::usleep(\$us);
}
function date(\$format, \$timestamp = null)
{
    return \\$self::date(\$format, \$timestamp);
}
EOPHP
            );
        }
    }
}
