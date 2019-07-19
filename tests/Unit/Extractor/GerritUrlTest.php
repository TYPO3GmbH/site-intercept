<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Extractor;

use App\Exception\DoNotCareException;
use App\Extractor\GerritUrl;
use PHPUnit\Framework\TestCase;

class GerritUrlTest extends TestCase
{
    public function constructorExtractsDataDataProvider()
    {
        return [
            'url' => [
                'https://review.typo3.org/48574',
                48574,
                null
            ],
            'url with slash at end' => [
                'https://review.typo3.org/48574/',
                48574,
                null
            ],
            'url with hash c' => [
                'https://review.typo3.org/#/c/48574',
                48574,
                null
            ],
            'url with hash c with slash at end' => [
                'https://review.typo3.org/#/c/48574/',
                48574,
                null
            ],
            'url with patch set' => [
                'https://review.typo3.org/48574/1',
                48574,
                1
            ],
            'url with patch set with slash at end' => [
                'https://review.typo3.org/48574/42/',
                48574,
                42
            ],
            'url with hash c with patch set' => [
                'https://review.typo3.org/#/c/48574/1',
                48574,
                1
            ],
            'url with hash c with patch set with slash at end' => [
                'https://review.typo3.org/#/c/48574/2/',
                48574,
                2
            ],
            'url with hash c with patch set with file' => [
                'https://review.typo3.org/#/c/48574/1/typo3/sysext/core/Documentation/Changelog/9.5.x/Index.rst',
                48574,
                1
            ],
            'new url style' => [
                'https://review.typo3.org/#/c/Packages/TYPO3.CMS/+/59676',
                59676,
                null
            ],
            'new url style with slash at end' => [
                'https://review.typo3.org/#/c/Packages/TYPO3.CMS/+/59676/',
                59676,
                null
            ],
            'new url style with patch set' => [
                'https://review.typo3.org/#/c/Packages/TYPO3.CMS/+/59676/1',
                59676,
                1
            ],
            'new url style with patch set with slash at end' => [
                'https://review.typo3.org/#/c/Packages/TYPO3.CMS/+/59676/42/',
                59676,
                42
            ],
            'new url style with patch set with file' => [
                'https://review.typo3.org/#/c/Packages/TYPO3.CMS/+/59676/42/typo3/sysext/core/Documentation/Changelog/9.5.x/Index.rst',
                59676,
                42
            ],
            'new url style of new interface' => [
                'https://review.typo3.org/c/Packages/TYPO3.CMS/+/59676',
                59676,
                null
            ],
            'new url style of new interface with slash at end' => [
                'https://review.typo3.org/c/Packages/TYPO3.CMS/+/59676/',
                59676,
                null
            ],
            'new url style of new interface with patch set' => [
                'https://review.typo3.org/c/Packages/TYPO3.CMS/+/59676/1',
                59676,
                1
            ],
            'new url style of new interface with patch set with slash at end' => [
                'https://review.typo3.org/c/Packages/TYPO3.CMS/+/59676/42/',
                59676,
                42
            ],
            'new url style of new interface with patch set with file' => [
                'https://review.typo3.org/c/Packages/TYPO3.CMS/+/59676/42/typo3/sysext/core/Documentation/Changelog/9.5.x/Index.rst',
                59676,
                42
            ],
            'security new url style of new interface' => [
                'https://review.typo3.org/c/Teams/Security/TYPO3v4-Core/+/59676',
                59676,
                null
            ],
            'security new url style of new interface with slash at end' => [
                'https://review.typo3.org/c/Teams/Security/TYPO3v4-Core/+/59676/',
                59676,
                null
            ],
            'security new url style of new interface with patch set' => [
                'https://review.typo3.org/c/Teams/Security/TYPO3v4-Core/+/59676/1',
                59676,
                1
            ],
            'security new url style of new interface with patch set with slash at end' => [
                'https://review.typo3.org/c/Teams/Security/TYPO3v4-Core/+/59676/42/',
                59676,
                42
            ],
            'security new url style of new interface with patch set with file' => [
                'https://review.typo3.org/c/Teams/Security/TYPO3v4-Core/+/59676/42/typo3/sysext/core/Documentation/Changelog/9.5.x/Index.rst',
                59676,
                42
            ],
        ];
    }

    /**
     * @test
     * @dataProvider constructorExtractsDataDataProvider
     */
    public function constructorExtractsData(string $url, int $changeId, ?int $patchSet)
    {
        $subject = new GerritUrl($url);
        $this->assertSame($changeId, $subject->changeId);
        $this->assertSame($patchSet, $subject->patchSet);
    }

    public function constructorThrowsIfChangeIdNotFoundDataProvider()
    {
        return [
            'empty string' => [
                ''
            ],
            'not a review.typo3.org url' => [
                'https://foo.typo3.org/48574/42/',
            ],
            'no change id' => [
                'https://review.typo3.org/',
            ],
            'no change id but hash with c' => [
                'https://review.typo3.org/#/c/',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider constructorThrowsIfChangeIdNotFoundDataProvider
     */
    public function constructorThrowsIfChangeIdNotFound(string $url)
    {
        $this->expectException(DoNotCareException::class);
        new GerritUrl($url);
    }
}
