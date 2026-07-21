<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional\Service;

use App\Service\IpAddressService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use T3G\LibTestHelper\Request\AssertRequestTrait;
use T3G\LibTestHelper\Request\RequestExpectation;
use T3G\LibTestHelper\Request\RequestPool;

final class IpAddressServiceTest extends KernelTestCase
{
    use AssertRequestTrait;

    public static function privateIpAddressIsIgnoredDataProvider(): \Generator
    {
        yield ['127.0.0.1'];
        yield ['192.168.10.5'];
    }

    #[DataProvider('privateIpAddressIsIgnoredDataProvider')]
    public function testPrivateIpAddressIsIgnored(string $ipAddress): void
    {
        $generalClient = $this->createMock(Client::class);
        self::getContainer()->set('guzzle.client.general', $generalClient);

        $ipAddressService = self::getContainer()->get(IpAddressService::class);
        $this->assertFalse($ipAddressService->isIpAddressToBeIgnored($ipAddress));
    }

    public static function ipAddressIsToBeIgnoredDataProvider(): \Generator
    {
        yield [
            'ipAddress' => '143.55.79.8', // Github: 143.55.64.0/20
            'expectedResult' => true,
            'expectedRequests' => ['github'],
        ];
        yield [
            'ipAddress' => '143.55.80.0', // Github: 143.55.64.0/20
            'expectedResult' => false,
            'expectedRequests' => ['github', 'bitbucket'],
        ];
        yield [
            'ipAddress' => '34.74.90.79', // Gitlab: 34.74.90.64/28
            'expectedResult' => true,
            'expectedRequests' => ['github'],
        ];
        yield [
            'ipAddress' => '34.74.90.80', // Gitlab: 34.74.90.64/28
            'expectedResult' => false,
            'expectedRequests' => ['github', 'bitbucket'],
        ];
        yield [
            'ipAddress' => '104.192.140.1', // Bitbucket: 104.192.136.0/21
            'expectedResult' => true,
            'expectedRequests' => ['github', 'bitbucket'],
        ];
        yield [
            'ipAddress' => '104.192.144.0', // Bitbucket: 104.192.136.0/21
            'expectedResult' => false,
            'expectedRequests' => ['github', 'bitbucket'],
        ];
    }

    #[DataProvider('ipAddressIsToBeIgnoredDataProvider')]
    public function testIpAddressIsToBeIgnored(string $ipAddress, bool $expectedResult, array $expectedRequests): void
    {
        $requestPool = new RequestPool();
        if (in_array('github', $expectedRequests, true)) {
            $requestPool->addRequestExpectation(new RequestExpectation(
                'GET',
                'https://api.github.com/meta',
                new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__ . '/../Fixtures/IpAddressPoolGithub.json'))
            ));
        }
        if (in_array('bitbucket', $expectedRequests, true)) {
            $requestPool->addRequestExpectation(new RequestExpectation(
                'GET',
                'https://ip-ranges.atlassian.com/',
                new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__ . '/../Fixtures/IpAddressPoolAtlassian.json'))
            ));
        }
        $generalClient = $this->createMock(Client::class);
        self::getContainer()->set('guzzle.client.general', $generalClient);
        $this->assertRequests($generalClient, $requestPool);

        $ipAddressService = self::getContainer()->get(IpAddressService::class);
        $this->assertSame($expectedResult, $ipAddressService->isIpAddressToBeIgnored($ipAddress));
    }
}
