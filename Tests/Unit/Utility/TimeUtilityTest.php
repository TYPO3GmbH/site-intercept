<?php
declare(strict_types = 1);
namespace T3G\Intercept\Tests\Unit\Utility;


use PHPUnit\Framework\TestCase;
use T3G\Intercept\Utility\TimeUtility;

class TimeUtilityTest extends TestCase
{
    /**
     * @test
     * @dataProvider secondsDataProvider
     *
     * @param int    $seconds
     * @param string $expected
     */
    public function convertSecondsToHumanReadableString(int $seconds, string $expected)
    {
        $result = TimeUtility::convertSecondsToHumanReadable($seconds);
        self::assertSame($expected, $result);
    }

    public function secondsDataProvider() : array
    {
        return [
            [21, '21s'],
            [120, '02m 00s'],
            [131, '02m 11s'],
            [1517, '25m 17s'],
            [4690, '1h 18m 10s']
        ];
    }
}
