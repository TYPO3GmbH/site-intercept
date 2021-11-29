<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Utility;

use App\Exception\DoNotCareException;
use App\Utility\BranchUtility;
use PHPUnit\Framework\TestCase;

class BranchUtilityTest extends TestCase
{
    public function resolveCoreMonoRepoBranchDataProvider(): array
    {
        return [
            'main' => [
                'main',
                'main'
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
            'web interface nightlyMain' => [
                'nightlyMain',
                'main'
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
    public function resolveCoreMonoRepoBranch(string $input, string $expected): void
    {
        $this->assertSame($expected, BranchUtility::resolveCoreMonoRepoBranch($input));
    }

    /**
     * @test
     */
    public function resolveCoreMonoRepoBranchThrowsOnInvalid(): void
    {
        $this->expectException(DoNotCareException::class);
        BranchUtility::resolveCoreMonoRepoBranch('foo42');
    }

    public function resolveCoreSplitBranchDataProvider(): array
    {
        return [
            'main' => [
                'main',
                'main'
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
    public function resolveCoreSplitBranch(string $input, string $expected): void
    {
        $this->assertSame($expected, BranchUtility::resolveCoreSplitBranch($input));
    }

    public function resolveCoreSplitBranchThrowsOnInvalidDataProvider(): array
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
    public function resolveCoreSplitBranchThrowsOnInvalid(string $input): void
    {
        $this->expectException(DoNotCareException::class);
        BranchUtility::resolveCoreSplitBranch($input);
    }
}
