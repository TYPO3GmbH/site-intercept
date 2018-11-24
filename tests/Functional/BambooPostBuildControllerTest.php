<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Client\BambooClient;
use App\Client\GerritClient;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class BambooPostBuildControllerTest extends TestCase
{
    /**
     * @test
     */
    public function bambooBuildIsTriggered()
    {
        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer();

        $bambooClient = $this->prophesize(BambooClient::class);
        $bambooClient
            ->get(
                file_get_contents(__DIR__ . '/Fixtures/BambooPostBuildGoodBambooDetailsUrl.txt'),
                require __DIR__ . '/Fixtures/BambooPostBuildGoodBambooDetailsHeader.php'
            )->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/BambooPostBuildGoodBambooDetailsResponse.php');
        $container->set('App\Client\BambooClient', $bambooClient->reveal());

        $gerritClient = $this->prophesize(GerritClient::class);
        $gerritClient
            ->post(
                file_get_contents(__DIR__ . '/Fixtures/BambooPostBuildGoodGerritPostUrl.txt'),
                require __DIR__ . '/Fixtures/BambooPostBuildGoodGerritPostData.php'
            )
            ->shouldBeCalled()
            ->willReturn(new Response());
        $container->set('App\Client\GerritClient', $gerritClient->reveal());

        $request = require __DIR__ . '/Fixtures/BambooPostBuildGoodRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }
}
