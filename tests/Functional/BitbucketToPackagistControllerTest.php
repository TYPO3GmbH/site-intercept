<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Client\PackagistClient;
use App\Kernel;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class BitbucketToPackagistControllerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    /**
     * @test
     */
    public function updateRequestIsSentToPackagist(): void
    {
        $packagistClientProphecy = $this->prophesize(PackagistClient::class);
        $packagistClientProphecy
            ->post(
                'https://packagist.org/api/update-package?username=horst&apiToken=dummyToken',
                [
                    'json' => [
                        'repository' => [
                            'url' => 'https://packagist.org/packages/t3g/google-ads',
                        ],
                    ],
                ]
            )->shouldBeCalled()
            ->willReturn(new Response());
        TestDoubleBundle::addProphecy(PackagistClient::class, $packagistClientProphecy);

        $kernel = new Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/BitbucketPushEvent.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function updateRequestReturnsErrorResponseFromClient(): void
    {
        $packagistClientProphecy = $this->prophesize(PackagistClient::class);
        $packagistClientProphecy
            ->post(
                'https://packagist.org/api/update-package?username=horst&apiToken=dummyToken',
                [
                    'json' => [
                        'repository' => [
                            'url' => 'https://packagist.org/packages/t3g/google-ads',
                        ],
                    ],
                ]
            )->shouldBeCalled()
            ->willReturn(new Response(406));
        TestDoubleBundle::addProphecy(PackagistClient::class, $packagistClientProphecy);

        $kernel = new Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/BitbucketPushEvent.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        self::assertSame(406, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function updateRequestReturnsErrorIfNoApiToken(): void
    {
        $request = Request::create('/bitbucketToPackagist?username=horst');
        $kernel = new Kernel('test', true);
        $kernel->boot();
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Missing apiToken or username in request.', $response->getContent());
    }

    /**
     * @test
     */
    public function updateRequestReturnsErrorIfNoUsername(): void
    {
        $request = Request::create('/bitbucketToPackagist?apiToken=dummyToken');
        $kernel = new Kernel('test', true);
        $kernel->boot();
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Missing apiToken or username in request.', $response->getContent());
    }

    /**
     * @test
     */
    public function updateRequestReturnsErrorIfNoPayloadGiven(): void
    {
        $request = Request::create('/bitbucketToPackagist?apiToken=dummyToken&username=foo');
        $kernel = new Kernel('test', true);
        $kernel->boot();
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Could not decode payload.', $response->getContent());
    }

    /**
     * @test
     */
    public function updateRequestReturnsErrorIfPayloadIsInvalid(): void
    {
        $request = Request::create('/bitbucketToPackagist?apiToken=dummyToken&username=foo', 'POST', [], [], [], [], json_encode(['name' => 'foo']));
        $kernel = new Kernel('test', true);
        $kernel->boot();
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid payload, missing \'eventKey\' or \'repository.name\'.', $response->getContent());
    }

    /**
     * @test
     */
    public function updateRequestReturnsErrorIfWrongEventTypeGiven(): void
    {
        $request = Request::create('/bitbucketToPackagist?apiToken=dummyToken&username=foo', 'POST', [], [], [], [], json_encode(['eventKey' => 'foo', 'repository' => ['name' => 'bar']]));
        $kernel = new Kernel('test', true);
        $kernel->boot();
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Wrong eventKey. Expected \'repo:refs_changed\' got \'foo\'', $response->getContent());
    }

    /**
     * @test
     */
    public function updateRequestReturnsErrorIfPackageIsNotKnown(): void
    {
        $request = Request::create('/bitbucketToPackagist?apiToken=dummyToken&username=foo', 'POST', [], [], [], [], json_encode(['eventKey' => 'repo:refs_changed', 'repository' => ['name' => 'foo']]));
        $kernel = new Kernel('test', true);
        $kernel->boot();
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Package not known.', $response->getContent());
    }
}
