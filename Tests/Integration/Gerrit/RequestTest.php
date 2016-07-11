<?php
declare(strict_types = 1);

namespace T3G\Intercept\Tests\Integration\Gerrit;

use Psr\Log\LoggerInterface;
use T3G\Intercept\Requests\CurlGerritPostRequests;

/**
 * Class CurlGerritPostRequestTest
 *
 * @@@@ WARNING! These tests trigger real requests! @@@@
 *
 * @package T3G\Intercept\Tests\Integration\Requests
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @return void
     */
    public function postRequest()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        // merged patch, so reviewing doesn't harm
        $apiPath = 'changes/48799/revisions/4/review';
        $curlGerritPostRequest = new CurlGerritPostRequests($loggerProphecy->reveal());
        $response = $curlGerritPostRequest->postRequest($apiPath, [
            'message' => 'integration test message',
            'labels' => [
                'Verified' => '+1'
            ]
        ]);
        self::assertSame(200, $response->getStatusCode());
    }

}
