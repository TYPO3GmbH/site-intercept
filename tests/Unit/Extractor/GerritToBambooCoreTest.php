<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Extractor;

use App\Exception\DoNotCareException;
use App\Extractor\GerritToBambooCore;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class GerritToBambooCoreTest extends TestCase
{
    /**
     * @test
     */
    public function constructorExtractsValues()
    {
        $subject = new GerritToBambooCore('https://review.typo3.org/48574', 42, 'master', 'Packages/TYPO3.CMS');
        $this->assertSame(48574, $subject->changeId);
        $this->assertSame(42, $subject->patchSet);
        $this->assertSame('CORE-GTC', $subject->bambooProject);
        $this->assertFalse($subject->isSecurity);
    }

    /**
     * @test
     */
    public function constructorExtractsValuesWithChangeUrlTrailingSlach()
    {
        $subject = new GerritToBambooCore('https://review.typo3.org/48574/', 42, 'master', 'Packages/TYPO3.CMS');
        $this->assertSame(48574, $subject->changeId);
        $this->assertSame(42, $subject->patchSet);
        $this->assertSame('CORE-GTC', $subject->bambooProject);
        $this->assertFalse($subject->isSecurity);
    }

    /**
     * @test
     */
    public function constructorExtractsChangeWithFullChangeUrl()
    {
        $subject = new GerritToBambooCore('https://review.typo3.org/#/c/58611/', 42, 'master', 'Packages/TYPO3.CMS');
        $this->assertSame(58611, $subject->changeId);
        $this->assertSame(42, $subject->patchSet);
        $this->assertSame('CORE-GTC', $subject->bambooProject);
        $this->assertFalse($subject->isSecurity);
    }

    /**
     * @test
     */
    public function constructorExtractsChangeWithFullChangeUrlIncludingPatchSet()
    {
        $subject = new GerritToBambooCore('https://review.typo3.org/#/c/58611/11', 42, 'master', 'Packages/TYPO3.CMS');
        $this->assertSame(58611, $subject->changeId);
        $this->assertSame(42, $subject->patchSet);
        $this->assertSame('CORE-GTC', $subject->bambooProject);
        $this->assertFalse($subject->isSecurity);
    }

    /**
     * @test
     */
    public function constructorExtractsChangeWithFullChangeUrlIncludingPatchSetSlash()
    {
        $subject = new GerritToBambooCore('https://review.typo3.org/#/c/58611/11/', 42, 'master', 'Packages/TYPO3.CMS');
        $this->assertSame(58611, $subject->changeId);
        $this->assertSame(42, $subject->patchSet);
        $this->assertSame('CORE-GTC', $subject->bambooProject);
        $this->assertFalse($subject->isSecurity);
    }

    /**
     * @test
     */
    public function constructorExtractsChangeWithStringChangeIdOnly()
    {
        $subject = new GerritToBambooCore('58611', 42, 'master', 'Packages/TYPO3.CMS');
        $this->assertSame(58611, $subject->changeId);
        $this->assertSame(42, $subject->patchSet);
        $this->assertSame('CORE-GTC', $subject->bambooProject);
        $this->assertFalse($subject->isSecurity);
    }

    /**
     * @test
     */
    public function constructorSetsIsSecurityToTrueAndCorrectBambooProjectWithSecurityProject()
    {
        $subject = new GerritToBambooCore('58611', 42, 'master', 'Teams/Security/TYPO3v4-Core');
        $this->assertSame(58611, $subject->changeId);
        $this->assertSame(42, $subject->patchSet);
        $this->assertSame('CORE-GTS', $subject->bambooProject);
        $this->assertTrue($subject->isSecurity);
    }

    /**
     * @test
     */
    public function constructorThrowsIfUrlIsBorked()
    {
        $this->expectException(DoNotCareException::class);
        new GerritToBambooCore('https://review.typo3.org/foo/', 42, 'some-other-branch', 'Packages/TYPO3.CMS');
    }

    /**
     * @test
     */
    public function constructorThrowsWithWrongBranch()
    {
        $this->expectException(DoNotCareException::class);
        new GerritToBambooCore('https://review.typo3.org/48574/', 42, 'some-other-branch', 'Packages/TYPO3.CMS');
    }

    /**
     * @test
     */
    public function constructorThrowsWithWrongEmptyPatchSet()
    {
        $this->expectException(DoNotCareException::class);
        new GerritToBambooCore('https://review.typo3.org/48574/', 0, 'master', 'Packages/TYPO3.CMS');
    }

    /**
     * @test
     */
    public function constructorThrowsWithEmptyProject()
    {
        $this->expectException(DoNotCareException::class);
        new GerritToBambooCore('https://review.typo3.org/48574/', 0, 'master', '');
    }
}
