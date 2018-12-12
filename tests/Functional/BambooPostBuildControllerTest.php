<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Client\BambooClient;
use App\Client\GerritClient;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class BambooPostBuildControllerTest extends TestCase
{
    /**
     * @test
     */
    public function gerritVoteIsCalled()
    {
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy
            ->get(
                file_get_contents(__DIR__ . '/Fixtures/BambooPostBuildGoodBambooDetailsUrl.txt'),
                require __DIR__ . '/Fixtures/BambooPostBuildGoodBambooDetailsHeader.php'
            )->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/BambooPostBuildGoodBambooDetailsResponse.php');
        TestDoubleBundle::addProphecy('App\Client\BambooClient', $bambooClientProphecy);

        $gerritClientProphecy = $this->prophesize(GerritClient::class);
        $gerritClientProphecy
            ->post(
                file_get_contents(__DIR__ . '/Fixtures/BambooPostBuildGoodGerritPostUrl.txt'),
                require __DIR__ . '/Fixtures/BambooPostBuildGoodGerritPostData.php'
            )
            ->shouldBeCalled()
            ->willReturn(new Response());
        TestDoubleBundle::addProphecy('App\Client\GerritClient', $gerritClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/BambooPostBuildGoodRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }
}
