<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Utility;

use App\Exception\DoNotCareException;
use App\Utility\BranchUtility;
use PHPUnit\Framework\TestCase;

class BranchUtilityTest extends TestCase
{
    public function resolveBambooProjectKeyDataProvider(): array
    {
        return [
            'master' => [
                'master',
                false,
                'CORE-GTC',
            ],
            'nightlyMaster' => [
                'nightlyMaster',
                false,
                'CORE-GTN',
            ],
            'straight 9.5' => [
                '9.5',
                false,
                'CORE-GTC95',
            ],
            'web interface 9.5 identifier' => [
                'branch9_5',
                false,
                'CORE-GTC95'
            ],
            'nightly 9.5' => [
                'nightly9_5',
                false,
                'CORE-GTN95',
            ],
            'core 8.7 old branch name' => [
                'TYPO3_8-7',
                false,
                'CORE-GTC87',
            ],
            'web interface 8.7 identifier' => [
                'branch8_7',
                false,
                'CORE-GTC87',
            ],
            'nightly 8.7' => [
                'nightly8_7',
                false,
                'CORE-GTN87',
            ],
            'core 7.6 old branch name' => [
                'TYPO3_7-6',
                false,
                'CORE-GTC76',
            ],
            'web interface 7.6 identifier' => [
                'branch7_6',
                false,
                'CORE-GTC76',
            ],
            'security master' => [
                'master',
                true,
                'CORE-GTS',
            ],
            'security straight 9.5' => [
                '9.5',
                true,
                'CORE-GTS95',
            ],
            'security web interface 9.5 identifier' => [
                'branch9_5',
                true,
                'CORE-GTS95'
            ],
            'security core 8.7 old branch name' => [
                'TYPO3_8-7',
                true,
                'CORE-GTS87',
            ],
            'security web interface 8.7 identifier' => [
                'branch8_7',
                true,
                'CORE-GTS87',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider resolveBambooProjectKeyDataProvider
     */
    public function branchToBambooProjectKey(string $input, bool $isSecurity, string $expected)
    {
        $this->assertSame($expected, BranchUtility::resolveBambooProjectKey($input, $isSecurity));
    }

    /**
     * @test
     */
    public function branchToBambooProjectKeyThrowsOnInvalid()
    {
        $this->expectException(DoNotCareException::class);
        BranchUtility::resolveBambooProjectKey('does-not-resolve', false);
    }

    /**
     * @test
     */
    public function branchToBambooProjectKeyThrowsOnInvalidSecurity()
    {
        $this->expectException(DoNotCareException::class);
        BranchUtility::resolveBambooProjectKey('does-not-resolve', true);
    }

    public function resolveCoreMonoRepoBranchDataProvider(): array
    {
        return [
            'master' => [
                'master',
                'master'
            ],
            'straight 23.42' => [
                '23.42',
                '23.42',
            ],
            'web interface 23.42 identifier' => [
                'branch23_42',
                '23.42',
            ],
            'straight 9.5' => [
                '9.5',
                '9.5',
            ],
            'web interface 9.5 identifier' => [
                'branch9_5',
                '9.5',
            ],
            'straight TYPO3_8-7' => [
                'TYPO3_8-7',
                'TYPO3_8-7'
            ],
            'web interface 8.7 identifier' => [
                'branch8_7',
                'TYPO3_8-7'
            ],
            'straight TYPO3_7-6' => [
                'TYPO3_7-6',
                'TYPO3_7-6'
            ],
            'web interface 7.6 identifier' => [
                'branch7_6',
                'TYPO3_7-6'
            ],
            'web interface nightlyMaster' => [
                'nightlyMaster',
                'master'
            ],
            'web interface nightly95' => [
                'nightly9_5',
                '9.5'
            ],
            'web interface nightly87' => [
                'nightly8_7',
                'TYPO3_8-7'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider resolveCoreMonoRepoBranchDataProvider
     */
    public function resolveCoreMonoRepoBranch(string $input, string $expected)
    {
        $this->assertSame($expected, BranchUtility::resolveCoreMonoRepoBranch($input));
    }

    /**
     * @test
     */
    public function resolveCoreMonoRepoBranchThrowsOnInvalid()
    {
        $this->expectException(DoNotCareException::class);
        BranchUtility::resolveCoreMonoRepoBranch('foo42');
    }

    public function resolveCoreSplitBranchDataProvider(): array
    {
        return [
            'master' => [
                'master',
                'master'
            ],
            '23.42' => [
                '23.42',
                '23.42',
            ],
            '9.5' => [
                '9.5',
                '9.5',
            ],
            'TYPO3_8-7' => [
                'TYPO3_8-7',
                '8.7'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider resolveCoreSplitBranchDataProvider
     */
    public function resolveCoreSplitBranch(string $input, string $expected)
    {
        $this->assertSame($expected, BranchUtility::resolveCoreSplitBranch($input));
    }

    public function resolveCoreSplitBranchThrowsOnInvalidDataProvider()
    {
        return [
            'invalid TYPO3_7-6' => [
                'TYPO3_7-6',
            ],
            'invalid 7.6' => [
                '7.6'
            ],
            'invalid something' => [
                'foo.bar',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider resolveCoreSplitBranchThrowsOnInvalidDataProvider
     */
    public function resolveCoreSplitBranchThrowsOnInvalid(string $input)
    {
        $this->expectException(DoNotCareException::class);
        BranchUtility::resolveCoreSplitBranch($input);
    }

    public function isBambooNightlyBuildDataProvider(): array
    {
        return [
            'master nightly' => [
                'CORE-GTN-1',
                true
            ],
            '9.5 nightly' => [
                'CORE-GTN95-42',
                true
            ],
            '8.7 nightly' => [
                'CORE-GTN87-23',
                true
            ],
            'something' => [
                'something',
                false
            ],
            'master pre-merge' => [
                'CORE-GTC-4711',
                false
            ],
            '9.5 pre-merge' => [
                'CORE-GTC95-42',
                false
            ],
            '8.7 pre-merge' => [
                'CORE-GTC87-23',
                false
            ],
            'master pre-merge security' => [
                'CORE-GTS-4711',
                false
            ],
            '9.5 pre-merge security' => [
                'CORE-GTS95-42',
                false
            ],
            '8.7 pre-merge security' => [
                'CORE-GTS87-23',
                false
            ],
        ];
    }

    /**
     * @test
     * @dataProvider isBambooNightlyBuildDataProvider
     */
    public function isBambooNightlyBuild(string $key, bool $expected)
    {
        $this->assertSame($expected, BranchUtility::isBambooNightlyBuild($key));
    }

    public function isBambooSecurityBuildDataProvider(): array
    {
        return [
            'master nightly' => [
                'CORE-GTN-1',
                false
            ],
            '9.5 nightly' => [
                'CORE-GTN95-42',
                false
            ],
            '8.7 nightly' => [
                'CORE-GTN87-23',
                false
            ],
            'something' => [
                'something',
                false
            ],
            'master pre-merge' => [
                'CORE-GTC-4711',
                false
            ],
            '9.5 pre-merge' => [
                'CORE-GTC95-42',
                false
            ],
            '8.7 pre-merge' => [
                'CORE-GTC87-23',
                false
            ],
            'master pre-merge security' => [
                'CORE-GTS-4711',
                true
            ],
            '9.5 pre-merge security' => [
                'CORE-GTS95-42',
                true
            ],
            '8.7 pre-merge security' => [
                'CORE-GTS87-23',
                true
            ],
        ];
    }

    /**
     * @test
     * @dataProvider isBambooSecurityBuildDataProvider
     */
    public function isBambooSecurityBuild(string $key, bool $expected)
    {
        $this->assertSame($expected, BranchUtility::isBambooSecurityBuild($key));
    }

    /**
     * @test
     */
    public function isSecurityProjectReturnsTrueForSecurityProject()
    {
        $this->assertTrue(BranchUtility::isSecurityProject('Teams/Security/TYPO3v4-Core'));
    }

    /**
     * @test
     */
    public function isSecurityProjectReturnsFalseForNonSecurityProject()
    {
        $this->assertFalse(BranchUtility::isSecurityProject('Packages/TYPO3.CMS'));
    }

    /**
     * @test
     */
    public function isSecurityProjectThrowsWithUnknownProject()
    {
        $this->expectException(DoNotCareException::class);
        BranchUtility::isSecurityProject('foo');
    }
}
