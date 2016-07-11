<?php
declare(strict_types = 1);

namespace T3G\Intercept\Tests\Integration\Github;


use Psr\Log\LoggerInterface;
use T3G\Intercept\Github\Client;


/**
 * Class GithubRequestsTest
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
    public function getIssueInformation()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $githubRequests = new Client($loggerProphecy->reveal());
        $url = 'https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1';
        $response = $githubRequests->getIssueInformation($url);
        self::assertSame(200, $response->getStatusCode());
    }
}
