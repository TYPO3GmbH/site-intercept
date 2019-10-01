<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Creator;

use App\Creator\GerritBuildStatusMessage;
use App\Extractor\BambooBuildStatus;
use PHPUnit\Framework\TestCase;

class GerritBuildStatusMessageTest extends TestCase
{
    /**
     * @test
     */
    public function messageContainsRelevantInformation()
    {
        $buildStatusProphecy = $this->prophesize(BambooBuildStatus::class);
        $buildStatusProphecy->buildDurationInSeconds = 1234;
        $buildStatusProphecy->prettyBuildCompletedTime = 'Fri, 23 Nov, 10:13 AM';
        $buildStatusProphecy->testSummary = '48491 passed';
        $buildStatusProphecy->buildUrl = 'https://bamboo.typo3.com/browse/CORE-GTC-30252';
        $subject = new GerritBuildStatusMessage($buildStatusProphecy->reveal());
        $this->assertRegExp('/20m 34s/', $subject->message);
        $this->assertRegExp('/Fri, 23 Nov, 10:13 AM/', $subject->message);
        $this->assertRegExp('/48491 passed/', $subject->message);
        $this->assertRegExp('/https:\/\/bamboo.typo3.com\/browse\/CORE-GTC-30252/', $subject->message);
    }
}
