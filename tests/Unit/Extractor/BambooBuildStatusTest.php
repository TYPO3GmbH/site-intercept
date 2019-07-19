<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Extractor;

use App\Extractor\BambooBuildStatus;
use PHPUnit\Framework\TestCase;

class BambooBuildStatusTest extends TestCase
{
    /**
     * @test
     */
    public function constructorExtractsValues()
    {
        $payload = '{"expand":"changes,metadata,plan,vcsRevisions,artifacts,comments,labels,jiraIssues,stages","link":{"href":"https://bamboo.typo3.com/rest/api/latest/result/T3G-AP-25","rel":"self"},"plan":{"shortName":"Apparel","shortKey":"AP","type":"chain","enabled":true,"link":{"href":"https://bamboo.typo3.com/rest/api/latest/plan/T3G-AP","rel":"self"},"key":"T3G-AP","name":"T3G - Apparel","planKey":{"key":"T3G-AP"}},"planName":"Apparel","projectName":"T3G","buildResultKey":"T3G-AP-25","lifeCycleState":"Finished","id":2359590,"buildStartedTime":"2016-06-18T18:59:12.562+02:00","prettyBuildStartedTime":"Sat, 18 Jun, 06:59 PM","buildCompletedTime":"2016-06-18T18:59:33.879+02:00","buildCompletedDate":"2016-06-18T18:59:33.879+02:00","prettyBuildCompletedTime":"Sat, 18 Jun, 06:59 PM","buildDurationInSeconds":21,"buildDuration":21317,"buildDurationDescription":"21 seconds","buildRelativeTime":"17 hours ago","vcsRevisionKey":"cf84362352469b6e18c4e9a6f693d161ed0c1925","vcsRevisions":{"size":1,"start-index":0,"max-result":1},"buildTestSummary":"6 passed","successfulTestCount":6,"failedTestCount":0,"quarantinedTestCount":0,"skippedTestCount":0,"continuable":false,"onceOff":false,"restartable":false,"notRunYet":false,"finished":true,"successful":true,"buildReason":"Manual run by <a href=\"https://bamboo.typo3.com/browse/user/susanne.moog\">Susanne Moog</a>","reasonSummary":"Manual run by <a href=\"https://bamboo.typo3.com/browse/user/susanne.moog\">Susanne Moog</a>","artifacts":{"size":0,"start-index":0,"max-result":0},"comments":{"size":0,"start-index":0,"max-result":0},"labels":{"size":2,"label":[{"name":"patchset-3"},{"name":"change-12345"}],"start-index":0,"max-result":2},"jiraIssues":{"size":3,"start-index":0,"max-result":3},"stages":{"size":1,"start-index":0,"max-result":1},"changes":{"size":0,"start-index":0,"max-result":0},"metadata":{"size":5,"start-index":0,"max-result":5},"key":"T3G-AP-25","planResultKey":{"key":"T3G-AP-25","entityKey":{"key":"T3G-AP"},"resultNumber":25},"state":"Successful","buildState":"Successful","number":25,"buildNumber":25}';
        $subject = new BambooBuildStatus($payload);
        $this->assertSame(12345, $subject->change);
        $this->assertSame(3, $subject->patchSet);
        $this->assertSame('https://bamboo.typo3.com/browse/T3G-AP-25', $subject->buildUrl);
        $this->assertTrue($subject->success);
        $this->assertSame('6 passed', $subject->testSummary);
        $this->assertSame('Sat, 18 Jun, 06:59 PM', $subject->prettyBuildCompletedTime);
        $this->assertSame(21, $subject->buildDurationInSeconds);
        $this->assertSame('Apparel', $subject->planName);
        $this->assertSame('T3G', $subject->projectName);
        $this->assertSame(25, $subject->buildNumber);
        $this->assertSame('T3G-AP-25', $subject->buildKey);
    }

    /**
     * @test
     */
    public function constructorExtractsValuesWithBambooUnderscoreFix()
    {
        $payload = '{"buildResultKey":"T3G-AP-25","prettyBuildStartedTime":"Sat, 18 Jun, 06:59 PM","prettyBuildCompletedTime":"Sat, 18 Jun, 06:59 PM","buildDurationInSeconds":21,"buildTestSummary":"6 passed","successful":true,"labels":{"size":2,"label":[{"name":"patchset-3_"},{"name":"change-12345_"}]}}';
        $subject = new BambooBuildStatus($payload);
        $this->assertSame(12345, $subject->change);
        $this->assertSame(3, $subject->patchSet);
        $this->assertSame('https://bamboo.typo3.com/browse/T3G-AP-25', $subject->buildUrl);
        $this->assertTrue($subject->success);
        $this->assertSame('6 passed', $subject->testSummary);
        $this->assertSame('Sat, 18 Jun, 06:59 PM', $subject->prettyBuildCompletedTime);
        $this->assertSame(21, $subject->buildDurationInSeconds);
    }
}
