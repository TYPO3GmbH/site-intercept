<?php
declare(strict_types = 1);

namespace T3G\Intercept\Tests\Functional\Library;

use Psr\Log\LoggerInterface;
use T3G\Intercept\Library\CurlBambooRequests;

/**
 * Class CurlBambooRequestsTest
 *
 * @@@@ WARNING! These tests trigger real requests! @@@@
 *
 * @package T3G\Intercept\Tests\Functional\Library
 */
class CurlBambooRequestsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CurlBambooRequests
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
        $this->curlBambooRequests->setProjectKey('CORE-TL');
        $response = $this->curlBambooRequests->triggerNewCoreBuild('foo', 3);
        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * @return \T3G\Intercept\Library\CurlBambooRequests
     */
    public function setUp()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->curlBambooRequests = new CurlBambooRequests($loggerProphecy->reveal());
    }
}
