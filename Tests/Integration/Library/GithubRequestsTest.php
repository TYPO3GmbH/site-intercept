<?php
declare(strict_types = 1);

namespace T3G\Intercept\Tests\Integration\Library;


use Psr\Log\LoggerInterface;
use T3G\Intercept\Library\GithubRequests;


/**
 * Class GithubRequestsTest
 *
 * @@@@ WARNING! These tests trigger real requests! @@@@
 *
 * @package T3G\Intercept\Tests\Integration\Library
 */
class GithubRequestsTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     * @return void
     */
    public function getIssueInformation()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $githubRequests = new GithubRequests($loggerProphecy->reveal());
        $url = 'https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1';
        $response = $githubRequests->getIssueInformation($url);
        self::assertSame(200, $response->getStatusCode());
    }
}
