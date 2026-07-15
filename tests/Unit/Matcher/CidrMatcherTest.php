<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Matcher;

use App\Matcher\CidrMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CidrMatcherTest extends TestCase
{
    public static function ipAddressMatchesDataProvider(): \Generator
    {
        yield ['192.168.1.42', true];
        yield ['192.168.2.1', false];
        yield ['192.168.97.1', false];
        yield ['10.0.0.1', true];
        yield ['10.255.255.255', true];
        yield ['2001:db8:1234::1', true];
        yield ['2008:af5:78fb::3', false];
        yield ['::1', true];
    }

    #[DataProvider('ipAddressMatchesDataProvider')]
    public function testIpAddressMatches(string $ipAddress, bool $expectMatch): void
    {
        $cidrList = [
            '192.168.1.0/24',
            '10.0.0.0/8',
            '2001:db8::/32',
            '::1',            // single host, no prefix
            '203.0.113.42',   // single IPv4 host
        ];
        $matcher = new CidrMatcher();

        $this->assertSame($expectMatch, $matcher->matches($ipAddress, $cidrList));
    }
}
