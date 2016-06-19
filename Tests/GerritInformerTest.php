<?php
declare(strict_types = 1);
namespace T3G\Tests;

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
        $postFields = [
            'message'=> "Build completed.",
            'labels' => [
                'Verified' => '+1'
            ]
        ];
        $buildInformation = [
            'patchset' => 3,
            'change' => 12345,
            'success' => true
        ];

        $gerritInformer = new GerritInformer($curlGerritPostRequest->reveal());
        $gerritInformer->voteOnGerrit($buildInformation);

        $curlGerritPostRequest->postRequest('changes/12345/revisions/3/review', $postFields)->shouldHaveBeenCalled();
    }
}
