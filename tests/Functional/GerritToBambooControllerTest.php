<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Client\BambooClient;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class GerritToBambooControllerTest extends TestCase
{
    /**
     * @test
     */
    public function bambooBuildIsTriggered()
    {
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy
            ->post(
                file_get_contents(__DIR__ . '/Fixtures/GerritToBambooGoodBambooPostUrl.txt'),
                require __DIR__ . '/Fixtures/GerritToBambooGoodBambooPostData.php'
            )->shouldBeCalled()
            ->willReturn(new Response());
        TestDoubleBundle::addProphecy('App\Client\BambooClient', $bambooClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/GerritToBambooGoodRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function bambooBuildIsNotTriggeredWithWrongBranch()
    {
        $bambooClient = $this->prophesize(BambooClient::class);
        $bambooClient->post(Argument::cetera())->shouldNotBeCalled();
        TestDoubleBundle::addProphecy('App\Client\BambooClient', $bambooClient);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/GerritToBambooBadRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }
}
