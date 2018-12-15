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
                'CORE-GTC',
            ],
            'nightlyMaster' => [
                'nightlyMaster',
                'CORE-GTN',
            ],
            'straight 9.5' => [
                '9.5',
                'CORE-GTC95',
            ],
            'web interface 9.5 identifier' => [
                'branch9_5',
                'CORE-GTC95'
            ],
            'nightly 9.5' => [
                'nightly9_5',
                'CORE-GTN95',
            ],
            'core 8.7 old branch name' => [
                'TYPO3_8-7',
                'CORE-GTC87',
            ],
            'web interface 8.7 identifier' => [
                'branch8_7',
                'CORE-GTC87',
            ],
            'nightly 8.7' => [
                'nightly8_7',
                'CORE-GTN87',
            ],
            'core 7.6 old branch name' => [
                'TYPO3_7-6',
                'CORE-GTC76',
            ],
            'web interface 7.6 identifier' => [
                'branch7_6',
                'CORE-GTC76',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider resolveBambooProjectKeyDataProvider
     */
    public function branchToBambooProjectKey(string $input, string $expected)
    {
        $this->assertSame($expected, BranchUtility::resolveBambooProjectKey($input));
    }

    /**
     * @test
     */
    public function branchToBambooProjectKeyThrowsOnInvalid()
    {
        $this->expectException(DoNotCareException::class);
        BranchUtility::resolveBambooProjectKey('does-not-resolve');
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
}
