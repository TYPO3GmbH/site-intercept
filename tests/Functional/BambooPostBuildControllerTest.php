<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Client\BambooClient;
use App\Client\GerritClient;
use App\Client\SlackClient;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

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
        TestDoubleBundle::addProphecy(GerritClient::class, $gerritClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/BambooPostBuildGoodRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function slackMessageForFailedNightlyIsSend()
    {
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy
            ->get(
                'latest/result/CORE-GTN-585?os_authType=basic&expand=labels',
                Argument::cetera()
            )->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/BambooPostBuildFailedNightlyBambooDetailsResponse.php');
        TestDoubleBundle::addProphecy('App\Client\BambooClient', $bambooClientProphecy);

        $slackClientProphecy = $this->prophesize(SlackClient::class);
        $slackClientProphecy->post(Argument::cetera())->shouldBeCalled()->willReturn(new Response());
        TestDoubleBundle::addProphecy(SlackClient::class, $slackClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/BambooPostBuildFailedNightlyBuildRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }
}
