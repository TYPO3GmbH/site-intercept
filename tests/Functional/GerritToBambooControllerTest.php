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
        $bambooClientProphecy->get(
            'latest/queue/CORE-GTC?stage=&os_authType=basic&executeAllStages=&bamboo.variable.changeUrl=58920&bamboo.variable.patchset=1',
            require __DIR__ . '/Fixtures/GerritToBambooGoodBambooPostData.php'
        )->willReturn(new Response());
        $bambooClientProphecy->get(
            'latest/result?includeAllStates=true&buildstate=Unknown&label=change-58920',
            require __DIR__ . '/Fixtures/GerritToBambooGoodBambooPostData.php'
        )->willReturn(new Response());
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
