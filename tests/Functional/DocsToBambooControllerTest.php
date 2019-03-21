<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Bundle\ClockMockBundle;
use App\Bundle\TestDoubleBundle;
use App\Client\BambooClient;
use App\Client\GeneralClient;
use App\Service\DocumentationBuildInformationService;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DocsToBambooControllerTest extends KernelTestCase
{
    public function setUp()
    {
        parent::setUp();
        self::bootKernel();
        DatabasePrimer::prime(self::$kernel);
    }
    /**
     * @test
     */
    public function bambooBuildIsTriggered()
    {
        ClockMockBundle::register(DocumentationBuildInformationService::class);
        ClockMockBundle::withClockMock(155309515.6937);

        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        $generalClientProphecy
            ->request('GET', 'https://raw.githubusercontent.com/TYPO3-Documentation/TYPO3CMS-Reference-CoreApi/latest/composer.json')
            ->shouldBeCalled()
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodRequestComposer.json')));
        TestDoubleBundle::addProphecy(GeneralClient::class, $generalClientProphecy);

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy
            ->post(
                file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodBambooPostUrl.txt'),
                require __DIR__ . '/Fixtures/DocsToBambooGoodBambooPostData.php'
            )->shouldBeCalled()
            ->willReturn(new Response());
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

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
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/DocsToBambooBadRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }
}
