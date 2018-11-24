<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Client\BambooClient;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class GerritToBambooControllerTest extends TestCase
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
        $bambooClient->post(
            file_get_contents(__DIR__ . '/Fixtures/GerritToBambooOutgoingBambooPostUrl.txt'),
            require(__DIR__ . '/Fixtures/GerritToBambooOutgoingBambooPostData.php')
        )->shouldBeCalled()
        ->willReturn(new Response());
        $container->set('App\Client\BambooClient', $bambooClient->reveal());

        $request = Request::create('/gerrit', 'POST', require(__DIR__ . '/Fixtures/GerritToBambooIncomingPost.php'));
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }
}
