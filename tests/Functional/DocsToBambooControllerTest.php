<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Client\BambooClient;
use App\Extractor\DocumentationBuildInformation;
use App\Service\DocumentationBuildInformationService;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class DocsToBambooControllerTest extends TestCase
{
    /**
     * @test
     */
    public function bambooBuildIsTriggered()
    {
        $documentationBuildInformationServiceProphecy = $this->prophesize(DocumentationBuildInformationService::class);
        TestDoubleBundle::addProphecy(DocumentationBuildInformationService::class, $documentationBuildInformationServiceProphecy);

        $documentationBuildInformationServiceProphecy->generateBuildInformation(Argument::any())->shouldBeCalled()->willReturn(
            new DocumentationBuildInformation('builds/1553095156937')
        );

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy
            ->post(
                file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodBambooPostUrl.txt'),
                require __DIR__ . '/Fixtures/DocsToBambooGoodBambooPostData.php'
            )->shouldBeCalled()
            ->willReturn(new Response());
        TestDoubleBundle::addProphecy('App\Client\BambooClient', $bambooClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/DocsToBambooGoodRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function bambooBuildIsNotTriggered()
    {
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy->post(Argument::cetera())->shouldNotBeCalled();
        TestDoubleBundle::addProphecy('App\Client\BambooClient', $bambooClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/DocsToBambooBadRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }
}
