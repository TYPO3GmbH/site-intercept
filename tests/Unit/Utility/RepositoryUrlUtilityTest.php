<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Utility;

use App\Utility\RepositoryUrlUtility;
use PHPUnit\Framework\TestCase;

class RepositoryUrlUtilityTest extends TestCase
{
    public static function extractRepositoryNameFromCloneUrlDataProvider(): \Iterator
    {
        yield ['git@github.com:typo3/typo3.git', 'typo3/typo3'];
        yield ['git@github.com:TYPO3GmbH/elts-9.5-release.git', 'TYPO3GmbH/elts-9.5-release'];
        yield ['git@github.com:TYPO3GmbH/elts-8.7-release.git', 'TYPO3GmbH/elts-8.7-release'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('extractRepositoryNameFromCloneUrlDataProvider')]
    public function testExtractRepositoryNameFromCloneUrlReturnsName(string $url, string $expectedName): void
    {
        $this->assertSame($expectedName, RepositoryUrlUtility::extractRepositoryNameFromCloneUrl($url));
    }

    public function testExtractRepositoryNameFromCloneUrlThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot extract repository from clone URL typo3/typo3');
        $this->expectExceptionCode(1632320303);

        RepositoryUrlUtility::extractRepositoryNameFromCloneUrl('typo3/typo3');
    }
}
