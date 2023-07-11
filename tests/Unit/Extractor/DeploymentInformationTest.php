<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Extractor;

use App\Exception\ComposerJsonInvalidException;
use App\Exception\DocsPackageDoNotCareBranch;
use App\Extractor\DeploymentInformation;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;

class DeploymentInformationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        ClockMock::register(DeploymentInformation::class);
        ClockMock::withClockMock(155309515.6937);
    }

    public function testComposerJsonSetsValuesAsExpected(): void
    {
        $composerJsonAsArray = [
            'name' => 'foobar/bazfnord',
            'type' => 'typo3-cms-extension',
            'require' => ['typo3/cms-core' => '^9.5'],
        ];

        $subject = new DeploymentInformation(
            $composerJsonAsArray['name'],
            $composerJsonAsArray['type'],
            'bazfnord',
            'https://github.com/lolli42/enetcache/',
            'https://raw.githubusercontent.com/lolli42/enetcache/main/composer.json',
            'main',
            '9.5',
            '9.5',
            '/tmp/foo',
            'bar'
        );

        self::assertSame('https://github.com/lolli42/enetcache/', $subject->repositoryUrl);
        self::assertSame('https://raw.githubusercontent.com/lolli42/enetcache/main/composer.json', $subject->publicComposerJsonUrl);
        self::assertSame('foobar', $subject->vendor);
        self::assertSame('bazfnord', $subject->name);
        self::assertSame('foobar/bazfnord', $subject->packageName);
        self::assertSame('main', $subject->sourceBranch);
        self::assertSame('p', $subject->typeShort);
        self::assertSame('extension', $subject->typeLong);
        self::assertSame('main', $subject->targetBranchDirectory);
        self::assertStringContainsString('/tmp/foo/bar/', $subject->absoluteDumpFile);
        self::assertStringContainsString('bar/', $subject->relativeDumpFile);
    }

    public function testArrayIsReturnedAsExpected(): void
    {
        $composerJsonAsArray = [
            'name' => 'foobar/bazfnord',
            'type' => 'typo3-cms-extension',
            'require' => ['typo3/cms-core' => '^9.5'],
        ];

        $expected = [
            'repository_url' => 'https://github.com/lolli42/enetcache/',
            'public_composer_json_url' => 'https://raw.githubusercontent.com/lolli42/enetcache/main/composer.json',
            'vendor' => 'foobar',
            'name' => 'bazfnord',
            'package_name' => 'foobar/bazfnord',
            'package_type' => 'typo3-cms-extension',
            'extension_key' => 'bazfnord',
            'source_branch' => 'main',
            'target_branch_directory' => 'main',
            'type_long' => 'extension',
            'type_short' => 'p',
        ];
        $subject = new DeploymentInformation(
            $composerJsonAsArray['name'],
            $composerJsonAsArray['type'],
            'bazfnord',
            'https://github.com/lolli42/enetcache/',
            'https://raw.githubusercontent.com/lolli42/enetcache/main/composer.json',
            'main',
            '9.5',
            '9.5',
            '/tmp/foo',
            'bar'
        );
        self::assertSame($expected, $subject->toArray());
    }

    public static function validPackageNameDataProvider(): array
    {
        return [
            ['husel/pusel', 'husel', 'pusel'],
            ['foo-bar/baz', 'foo-bar', 'baz'],
            ['foo/bar-husel', 'foo', 'bar-husel'],
            ['husel_pusel/foobar', 'husel_pusel', 'foobar'],
            ['foobar/baz_42', 'foobar', 'baz_42'],
        ];
    }

    /**
     * @dataProvider validPackageNameDataProvider
     */
    public function testPackageNamePartsAreCorrectlyResolved(string $packageName, string $expectedVendor, string $expectedName): void
    {
        $composerJsonAsArray = [
            'name' => $packageName,
            'type' => 'typo3-cms-extension',
            'require' => ['typo3/cms-core' => '^9.5'],
        ];

        $subject = new DeploymentInformation(
            $composerJsonAsArray['name'],
            $composerJsonAsArray['type'],
            $expectedName,
            'https://github.com/lolli42/enetcache/',
            'https://raw.githubusercontent.com/lolli42/enetcache/main/composer.json',
            'main',
            '9.5',
            '9.5',
            '/tmp/foo',
            'bar'
        );
        self::assertSame($expectedVendor, $subject->vendor);
        self::assertSame($expectedName, $subject->name);
        self::assertSame($packageName, $subject->packageName);
    }

    public static function invalidPackageNameDataProvider(): array
    {
        return [
            ['', 1558019290],
            ['baz', 1553082490],
            ['3245345', 1553082490],
            ['husel_pusel:foobar', 1553082490],
            ['../enetcache', 1553082490],
            ['lolli/../', 1553082490],
            ['lolli/../enetcache', 1553082490],
        ];
    }

    /**
     * @dataProvider invalidPackageNameDataProvider
     */
    public function testInvalidPackageNameThrowException(?string $packageName, int $expectedExceptionCode): void
    {
        $this->expectException(ComposerJsonInvalidException::class);
        $this->expectExceptionCode($expectedExceptionCode);

        $composerJsonAsArray = [
            'name' => $packageName,
            'type' => 'typo3-cms-extension',
        ];
        new DeploymentInformation(
            $composerJsonAsArray['name'],
            $composerJsonAsArray['type'],
            'not_given',
            'https://github.com/lolli42/enetcache/',
            'https://raw.githubusercontent.com/lolli42/enetcache/main/composer.json',
            'main',
            '9.5',
            '9.5',
            '/tmp/foo',
            'bar'
        );
    }

    public static function packageTypeDataProvider(): array
    {
        return [
            'manual' => ['typo3-cms-documentation', 'foobar/bazfnord', 'manual', 'm'],
            'core' => ['typo3-cms-framework', 'foobar/bazfnord', 'core-extension', 'c'],
            'extension' => ['typo3-cms-extension', 'foobar/bazfnord', 'extension', 'p'],
            'docs homepage' => ['', 'typo3/docs-homepage', 'docs-home', 'h'],
            'viewhelper reference' => ['', 'typo3/view-helper-reference', 'other', 'other'],
            'typo3 surf' => ['', 'typo3/surf', 'other', 'other'],
            'typo3 tailor' => ['', 'typo3/tailor', 'other', 'other'],
            'typo3 fluid' => ['', 'typo3fluid/fluid', 'other', 'other'],
        ];
    }

    /**
     * @dataProvider packageTypeDataProvider
     */
    public function testPackageTypePartsAreCorrectlyResolved(string $type, string $packageName, string $expectedLong, string $expectedShort): void
    {
        $composerJsonAsArray = [
            'name' => $packageName,
            'type' => $type,
            'require' => ['typo3/cms-core' => '^9.5'],
        ];
        $subject = new DeploymentInformation(
            $composerJsonAsArray['name'],
            $composerJsonAsArray['type'],
            'bazfnord',
            'https://github.com/lolli42/enetcache/',
            'https://raw.githubusercontent.com/lolli42/enetcache/main/composer.json',
            'main',
            '9.5',
            '9.5',
            '/tmp/foo',
            'bar'
        );

        self::assertSame($expectedLong, $subject->typeLong);
        self::assertSame($expectedShort, $subject->typeShort);
    }

    public function testDocsHomeTypeIsDetected(): void
    {
        $composerJsonAsArray = [
            'name' => 'typo3/docs-homepage',
            'type' => 'does-not-matter-here',
        ];
        $subject = new DeploymentInformation(
            $composerJsonAsArray['name'],
            $composerJsonAsArray['type'],
            'bazfnord',
            'https://github.com/TYPO3-Documentation/DocsTypo3Org-Homepage.git',
            'https://something',
            'main',
            '9.5',
            '9.5',
            '/tmp/foo',
            'bar'
        );

        self::assertSame('docs-home', $subject->typeLong);
        self::assertSame('h', $subject->typeShort);
    }

    public static function validBranchNameDataProvider(): array
    {
        return [
            [
                'typo3-cms-extension',
                'main',
                'main',
            ],
            [
                'typo3-cms-extension',
                'latest',
                'main',
            ],
            [
                'typo3-cms-extension',
                'documentation-draft',
                'draft',
            ],
            [
                'typo3-cms-extension',
                '1.2.3',
                '1.2',
            ],
            [
                'typo3-cms-extension',
                'v1.5.8',
                '1.5',
            ],
            [
                'typo3-cms-framework',
                'main',
                'main',
            ],
            [
                'typo3-cms-framework',
                'latest',
                'main',
            ],
            [
                'typo3-cms-framework',
                'documentation-draft',
                'draft',
            ],
            [
                'typo3-cms-framework',
                '1.2',
                '1.2',
            ],
            [
                'typo3-cms-framework',
                '1-2',
                '1.2',
            ],
            [
                'typo3-cms-framework',
                '1_2',
                '1.2',
            ],
            [
                'typo3-cms-framework',
                'v1.2',
                '1.2',
            ],
            [
                'typo3-cms-framework',
                'v1-2',
                '1.2',
            ],
            [
                'typo3-cms-framework',
                'v1_2',
                '1.2',
            ],
            [
                'typo3-cms-framework',
                '1',
                '1',
            ],
            [
                'typo3-cms-framework',
                'v1',
                '1',
            ],
            [
                'typo3-cms-documentation',
                'main',
                'main',
            ],
            [
                'typo3-cms-documentation',
                'latest',
                'main',
            ],
            [
                'typo3-cms-documentation',
                'documentation-draft',
                'draft',
            ],
            [
                'typo3-cms-documentation',
                '1.2',
                '1.2',
            ],
            [
                'typo3-cms-documentation',
                '1-2',
                '1.2',
            ],
            [
                'typo3-cms-documentation',
                '1_2',
                '1.2',
            ],
            [
                'typo3-cms-documentation',
                'v1.2',
                '1.2',
            ],
            [
                'typo3-cms-documentation',
                'v1-2',
                '1.2',
            ],
            [
                'typo3-cms-documentation',
                'v1_2',
                '1.2',
            ],
            [
                'typo3-cms-documentation',
                '1',
                '1',
            ],
            [
                'typo3-cms-documentation',
                'v1',
                '1',
            ],
        ];
    }

    /**
     * @dataProvider validBranchNameDataProvider
     */
    public function testBranchNamesAreNormalized(string $type, string $branch, string $expectedBranch): void
    {
        $composerJsonAsArray = [
            'name' => 'foobar/bazfnord',
            'type' => $type,
            'require' => ['typo3/cms-core' => '^9.5'],
        ];
        $subject = new DeploymentInformation(
            $composerJsonAsArray['name'],
            $composerJsonAsArray['type'],
            'bazfnord',
            'https://github.com/lolli42/enetcache/',
            'https://something',
            $branch,
            '9.5',
            '9.5',
            '/tmp/foo',
            'bar'
        );

        self::assertSame($expectedBranch, $subject->targetBranchDirectory);
    }

    public static function invalidBranchNameDataProvider(): array
    {
        return [
            [
                'typo3-cms-extension',
                '1.2',
                1557498335,
            ],
            [
                'typo3-cms-extension',
                '',
                1557498335,
            ],
            [
                'typo3-cms-extension',
                'foo',
                1557498335,
            ],
            [
                'typo3-cms-extension',
                '1.2-dev',
                1557498335,
            ],
            [
                'typo3-cms-extension',
                '1..2',
                1557498335,
            ],
            [
                'typo3-cms-extension',
                '1.2.3.4',
                1557498335,
            ],
            [
                'typo3-cms-extension',
                'v1.2',
                1557498335,
            ],
            [
                'typo3-cms-framework',
                '1.2.3',
                1557503542,
            ],
            [
                'typo3-cms-framework',
                'v1.2.3',
                1557503542,
            ],
            [
                'typo3-cms-documentation',
                '1.2.3',
                1557503542,
            ],
            [
                'typo3-cms-documentation',
                '1.2.3',
                1557503542,
            ],
        ];
    }

    /**
     * @dataProvider invalidBranchNameDataProvider
     */
    public function testInvalidBranchNamesThrowException(string $type, string $branch, int $exceptionCode): void
    {
        $this->expectException(DocsPackageDoNotCareBranch::class);
        $this->expectExceptionCode($exceptionCode);

        $composerJsonAsArray = [
            'name' => 'foobar/bazfnord',
            'type' => $type,
        ];
        new DeploymentInformation(
            $composerJsonAsArray['name'],
            $composerJsonAsArray['type'],
            'bazfnord',
            'https://github.com/lolli42/enetcache/',
            'https://something',
            $branch,
            '9.5',
            '9.5',
            '/tmp/foo',
            'bar'
        );
    }
}
