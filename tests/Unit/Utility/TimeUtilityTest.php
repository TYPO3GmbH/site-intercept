<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Utility;

use App\Utility\TimeUtility;
use PHPUnit\Framework\TestCase;

class TimeUtilityTest extends TestCase
{
    /**
     * @return array
     */
    public function secondsDataProvider(): array
    {
        return [
            [21, '21s'],
            [120, '02m 00s'],
            [131, '02m 11s'],
            [1517, '25m 17s'],
            [4690, '1h 18m 10s']
        ];
    }

    /**
     * @test
     * @dataProvider secondsDataProvider
     *
     * @param int $seconds
     * @param string $expected
     */
    public function convertSecondsToHumanReadableString(int $seconds, string $expected)
    {
        $result = TimeUtility::convertSecondsToHumanReadable($seconds);
        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function hasTimezones()
    {
        $data = TimeUtility::timeZones();

        // Check all continents
        $this->assertArrayHasKey('Europe/Berlin', $data);
        $this->assertArrayHasKey('UTC', $data);
        $this->assertArrayHasKey('Africa/Algiers', $data);
        $this->assertArrayHasKey('Antarctica/Mawson', $data);
        $this->assertArrayHasKey('Pacific/Apia', $data);
        $this->assertArrayHasKey('Asia/Kabul', $data);
        $this->assertArrayHasKey('Australia/Darwin', $data);
        $this->assertArrayHasKey('America/New_York', $data);
        $this->assertArrayHasKey('Indian/Mahe', $data);
    }
}
