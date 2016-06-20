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
        $message = 'Build completed at: https://bamboo.typo3.com/browse/T3G-AP-25';
        $postFields = [
            'message'=> $message,
            'labels' => [
                'Verified' => '+1'
            ]
        ];
        $buildInformation = [
            'patchset' => 3,
            'change' => 12345,
            'success' => true,
            'buildUrl' => 'https://bamboo.typo3.com/browse/T3G-AP-25'
        ];

        $gerritInformer = new GerritInformer($curlGerritPostRequest->reveal());
        $gerritInformer->voteOnGerrit($buildInformation);

        $curlGerritPostRequest->postRequest('changes/12345/revisions/3/review', $postFields)->shouldHaveBeenCalled();
    }
}
