<?php
declare(strict_types = 1);
namespace T3G\Intercept\Tests;

use Prophecy\Argument;
use T3G\Intercept\BambooStatusInformation;
use T3G\Intercept\Library\CurlBambooGetRequest;

class BambooInformationRequestBuilderTest extends \PHPUnit_Framework_TestCase
{
    protected $exampleResponse = '{"expand":"changes,metadata,plan,vcsRevisions,artifacts,comments,labels,jiraIssues,stages","link":{"href":"https://bamboo.typo3.com/rest/api/latest/result/T3G-AP-25","rel":"self"},"plan":{"shortName":"Apparel","shortKey":"AP","type":"chain","enabled":true,"link":{"href":"https://bamboo.typo3.com/rest/api/latest/plan/T3G-AP","rel":"self"},"key":"T3G-AP","name":"T3G - Apparel","planKey":{"key":"T3G-AP"}},"planName":"Apparel","projectName":"T3G","buildResultKey":"T3G-AP-25","lifeCycleState":"Finished","id":2359590,"buildStartedTime":"2016-06-18T18:59:12.562+02:00","prettyBuildStartedTime":"Sat, 18 Jun, 06:59 PM","buildCompletedTime":"2016-06-18T18:59:33.879+02:00","buildCompletedDate":"2016-06-18T18:59:33.879+02:00","prettyBuildCompletedTime":"Sat, 18 Jun, 06:59 PM","buildDurationInSeconds":21,"buildDuration":21317,"buildDurationDescription":"21 seconds","buildRelativeTime":"17 hours ago","vcsRevisionKey":"cf84362352469b6e18c4e9a6f693d161ed0c1925","vcsRevisions":{"size":1,"start-index":0,"max-result":1},"buildTestSummary":"6 passed","successfulTestCount":6,"failedTestCount":0,"quarantinedTestCount":0,"skippedTestCount":0,"continuable":false,"onceOff":false,"restartable":false,"notRunYet":false,"finished":true,"successful":true,"buildReason":"Manual run by <a href=\"https://bamboo.typo3.com/browse/user/susanne.moog\">Susanne Moog</a>","reasonSummary":"Manual run by <a href=\"https://bamboo.typo3.com/browse/user/susanne.moog\">Susanne Moog</a>","artifacts":{"size":0,"start-index":0,"max-result":0},"comments":{"size":0,"start-index":0,"max-result":0},"labels":{"size":2,"label":[{"name":"patchset-3"},{"name":"change-12345"}],"start-index":0,"max-result":2},"jiraIssues":{"size":3,"start-index":0,"max-result":3},"stages":{"size":1,"start-index":0,"max-result":1},"changes":{"size":0,"start-index":0,"max-result":0},"metadata":{"size":5,"start-index":0,"max-result":5},"key":"T3G-AP-25","planResultKey":{"key":"T3G-AP-25","entityKey":{"key":"T3G-AP"},"resultNumber":25},"state":"Successful","buildState":"Successful","number":25,"buildNumber":25}';

    /**
     * @test
     * @return void
     */
    public function extractExtractsPatchsetAndChangeNumberFromCurlStatusResponse()
    {
        $curlBambooRequest = $this->prophesize(CurlBambooGetRequest::class);
        $curlBambooRequest->getBuildStatus(Argument::any())->willReturn($this->exampleResponse);
        $bambooInformationRequestBuilder = new BambooStatusInformation($curlBambooRequest->reveal());
        $result = $bambooInformationRequestBuilder->transform('CORE-GTC-42');

        $expected = [
            'patchset' => 3,
            'change' => 12345,
            'success' => true
        ];

        self::assertSame($expected, $result);
    }

}
