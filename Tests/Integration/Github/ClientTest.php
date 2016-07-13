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
class ClientTest extends \PHPUnit_Framework_TestCase
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
        $githubRequests->get($url);
    }

    /**
     * @test
     * @return void
     */
    public function patchTest()
    {
        $url = 'https://api.github.com/repos/psychomieze/TYPO3.CMS/pulls/1';
        $data = [
            'state' => 'closed'
        ];

        $client = new Client();
        $client->patch($url, $data);
    }

    /**
     * @test
     * @return void
     */
    public function postTest()
    {
        $url = 'https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1/comments';
        $arr = ['body' => 'Thank you for your contribution to TYPO3. We are using Gerrit Code Review for our contributions and' .
        ' took the liberty to convert your pull request to a review in our review system. add a link to https://review.typo3.org/12345' . "\n"];

        $client = new Client();
        $client->post($url, $arr);
    }

    /**
     * @test
     */
    public function putTest()
    {
        $url = 'https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1/lock';

        $client = new Client();
        $client->put($url);
    }
}
