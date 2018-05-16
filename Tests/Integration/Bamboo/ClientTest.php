<?php
declare(strict_types = 1);

namespace T3G\Intercept\Tests\Integration\Bamboo;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use T3G\Intercept\Bamboo\Client;

/**
 * Class CurlBambooRequestsTest
 *
 * @@@@ WARNING! These tests trigger real requests! @@@@
 *
 * @package T3G\Intercept\Tests\Functional\Requests
 */
class ClientTest extends TestCase
{
    /**
     * @var Client
     */
    protected $curlBambooRequests;

    /**
     * @test
     * @return void
     */
    public function getBuildStatus()
    {
        $buildStatus = $this->curlBambooRequests->getBuildStatus('T3G-IN-20');
        $status = json_decode((string)$buildStatus->getBody(), true);
        self::assertSame('T3G', $status['projectName']);
    }

    /**
     * @test
     * @return void
     */
    public function triggerNewBuild()
    {
        // Trigger test build in lollis test build project not in "real" core project
        $this->curlBambooRequests->setBranchToProjectKey(['master' => 'CORE-TL']);
        $response = $this->curlBambooRequests->triggerNewCoreBuild('foo', 3, 'master');
        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * @return Client
     */
    public function setUp()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->curlBambooRequests = new Client($loggerProphecy->reveal());
    }
}
