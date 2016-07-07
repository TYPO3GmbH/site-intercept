<?php
declare(strict_types = 1);
namespace T3G\Intercept\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use T3G\Intercept\GerritInformer;
use T3G\Intercept\Library\CurlGerritPostRequest;

class GerritInformerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     * @return void
     */
    public function voteOnGerritSendsRequestToVote_Success()
    {
        $curlGerritPostRequest = $this->prophesize(CurlGerritPostRequest::class);
        $curlGerritPostRequest->postRequest(Argument::cetera())->willReturn(new Response());

        $message = "Completed build in 21s on Sat, 18 Jun, 06:59 PM\nTest Summary: 6 passed\nFind logs and detail information at https://bamboo.typo3.com/browse/T3G-AP-25";
        $postFields = [
            'message'=> $message,
            'labels' => [
                'Verified' => '+1'
            ]
        ];
        $buildInformation = [
            'patchset' => 3,
            'change' => 12345,
            'buildUrl' => 'https://bamboo.typo3.com/browse/T3G-AP-25',
            'success' => true,
            'buildTestSummary' => '6 passed',
            'prettyBuildCompletedTime' => 'Sat, 18 Jun, 06:59 PM',
            'buildDurationInSeconds' => 21
        ];

        $gerritInformer = new GerritInformer($curlGerritPostRequest->reveal());
        $gerritInformer->voteOnGerrit($buildInformation);

        $curlGerritPostRequest->postRequest('changes/12345/revisions/3/review', $postFields)->shouldHaveBeenCalled();
    }
}
