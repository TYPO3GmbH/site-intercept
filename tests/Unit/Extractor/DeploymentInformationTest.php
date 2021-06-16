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

    /**
     * @test
     */
    public function composerJsonSetsValuesAsExpected(): void
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
            'https://raw.githubusercontent.com/lolli42/enetcache/master/composer.json',
            'master',
            '9.5',
            '9.5',
            '/tmp/foo',
            'bar'
        );

        $this->assertSame('https://github.com/lolli42/enetcache/', $subject->repositoryUrl);
        $this->assertSame('https://raw.githubusercontent.com/lolli42/enetcache/master/composer.json', $subject->publicComposerJsonUrl);
        $this->assertSame('foobar', $subject->vendor);
        $this->assertSame('bazfnord', $subject->name);
        $this->assertSame('foobar/bazfnord', $subject->packageName);
        $this->assertSame('master', $subject->sourceBranch);
        $this->assertSame('p', $subject->typeShort);
        $this->assertSame('extension', $subject->typeLong);
        $this->assertSame('master', $subject->targetBranchDirectory);
        $this->assertStringContainsString('/tmp/foo/bar/', $subject->absoluteDumpFile);
        $this->assertStringContainsString('bar/', $subject->relativeDumpFile);
    }

    /**
     * @test
     */
    public function arrayIsReturnedAsExpected(): void
    {
        $composerJsonAsArray = [
            'name' => 'foobar/bazfnord',
            'type' => 'typo3-cms-extension',
            'require' => ['typo3/cms-core' => '^9.5'],
        ];

        $expected = [
            'repository_url' => 'https://github.com/lolli42/enetcache/',
            'public_composer_json_url' => 'https://raw.githubusercontent.com/lolli42/enetcache/master/composer.json',
            'vendor' => 'foobar',
            'name' => 'bazfnord',
            'package_name' => 'foobar/bazfnord',
            'package_type' => 'typo3-cms-extension',
            'extension_key' => 'bazfnord',
            'source_branch' => 'master',
            'target_branch_directory' => 'master',
            'type_long' => 'extension',
            'type_short' => 'p',
        ];
        $subject = new DeploymentInformation(
            $composerJsonAsArray['name'],
            $composerJsonAsArray['type'],
            'bazfnord',
            'https://github.com/lolli42/enetcache/',
            'https://raw.githubusercontent.com/lolli42/enetcache/master/composer.json',
            'master',
            '9.5',
            '9.5',
            '/tmp/foo',
            'bar'
        );
        $this->assertSame($expected, $subject->toArray());
    }

    /**
     * @return array
     */
    public function validPackageNameDataProvider(): array
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
     * @param string $packageName
     * @param string $expectedVendor
     * @param string $expectedName
     * @dataProvider validPackageNameDataProvider
     * @test
     */
    public function packageNamePartsAreCorrectlyResolved(string $packageName, string $expectedVendor, string $expectedName): void
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
            'https://raw.githubusercontent.com/lolli42/enetcache/master/composer.json',
            'master',
            '9.5',
            '9.5',
            '/tmp/foo',
            'bar'
        );
        $this->assertSame($expectedVendor, $subject->vendor);
        $this->assertSame($expectedName, $subject->name);
        $this->assertSame($packageName, $subject->packageName);
    }

    /**
     * @return array
     */
    public function invalidPackageNameDataProvider(): array
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
     * @param string $packageName
     * @param int $expectedExceptionCode
     * @dataProvider invalidPackageNameDataProvider
     * @test
     */
    public function invalidPackageNameThrowException(?string $packageName, int $expectedExceptionCode): void
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
            'https://raw.githubusercontent.com/lolli42/enetcache/master/composer.json',
            'master',
            '9.5',
            '9.5',
            '/tmp/foo',
            'bar'
        );
    }

    /**
     * @return array
     */
    public function packageTypeDataProvider(): array
    {
        return [
            'manual' => ['typo3-cms-documentation', 'foobar/bazfnord', 'manual', 'm'],
            'core' => ['typo3-cms-framework', 'foobar/bazfnord', 'core-extension', 'c'],
            'extension' => ['typo3-cms-extension', 'foobar/bazfnord', 'extension', 'p'],
            'docs homepage' => ['', 'typo3/docs-homepage', 'docs-home', 'h'],
            'viewhelper reference' => ['', 'typo3/view-helper-reference', 'other', 'other'],
            'typo3 surf' => ['', 'typo3/surf', 'other', 'other'],
            'typo3 tailor' => ['', 'typo3/tailor', 'other', 'other'],
        ];
    }

    /**
     * @param string $type
     * @param string $expectedLong
     * @param string $expectedShort
     * @dataProvider packageTypeDataProvider
     * @test
     */
    public function packageTypePartsAreCorrectlyResolved(string $type, string $packageName, string $expectedLong, string $expectedShort): void
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
            'https://raw.githubusercontent.com/lolli42/enetcache/master/composer.json',
            'master',
            '9.5',
            '9.5',
            '/tmp/foo',
            'bar'
        );

        $this->assertSame($expectedLong, $subject->typeLong);
        $this->assertSame($expectedShort, $subject->typeShort);
    }

    /**
     * @test
     */
    public function docsHomeTypeIsDetected(): void
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
            'master',
            '9.5',
            '9.5',
            '/tmp/foo',
            'bar'
        );

        $this->assertSame('docs-home', $subject->typeLong);
        $this->assertSame('h', $subject->typeShort);
    }

    /**
     * @return array
     */
    public function invalidPackageTypeDataProvider(): array
    {
        return [
            'empty string' => [
                '',
                1558019479
            ],
            'something else' => [
                'something',
                1557490474
            ]
        ];
    }

    /**
     * @param string $type
     * @param int $exceptionCode
     * @dataProvider invalidPackageTypeDataProvider
     * @test
     */
    public function invalidPackageTypesThrowException(?string $type, int $exceptionCode): void
    {
        $this->expectException(ComposerJsonInvalidException::class);
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
            'https://raw.githubusercontent.com/lolli42/enetcache/master/composer.json',
            'master',
            '9.5',
            '9.5',
            '/tmp/foo',
            'bar'
        );
    }

    /**
     * @return array
     */
    public function validBranchNameDataProvider(): array
    {
        return [
            [
                'typo3-cms-extension',
                'master',
                'master',
            ],
            [
                'typo3-cms-extension',
                'latest',
                'master',
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
                'master',
                'master',
            ],
            [
                'typo3-cms-framework',
                'latest',
                'master',
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
                'master',
                'master',
            ],
            [
                'typo3-cms-documentation',
                'latest',
                'master',
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
     * @param string $type
     * @param string $branch
     * @param string $expectedBranch
     * @dataProvider validBranchNameDataProvider
     * @test
     */
    public function branchNamesAreNormalized(string $type, string $branch, string $expectedBranch): void
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

        $this->assertSame($expectedBranch, $subject->targetBranchDirectory);
    }

    /**
     * @return array
     */
    public function invalidBranchNameDataProvider(): array
    {
        return [
            [
                'typo3-cms-extension',
                '1.2',
                1557498335
            ],
            [
                'typo3-cms-extension',
                '',
                1557498335
            ],
            [
                'typo3-cms-extension',
                'foo',
                1557498335
            ],
            [
                'typo3-cms-extension',
                '1.2-dev',
                1557498335
            ],
            [
                'typo3-cms-extension',
                '1..2',
                1557498335
            ],
            [
                'typo3-cms-extension',
                '1.2.3.4',
                1557498335
            ],
            [
                'typo3-cms-extension',
                'v1.2',
                1557498335
            ],
            [
                'typo3-cms-framework',
                '1.2.3',
                1557503542
            ],
            [
                'typo3-cms-framework',
                'v1.2.3',
                1557503542
            ],
            [
                'typo3-cms-documentation',
                '1.2.3',
                1557503542
            ],
            [
                'typo3-cms-documentation',
                '1.2.3',
                1557503542
            ],
        ];
    }

    /**
     * @param string $type
     * @param string $branch
     * @param int $exceptionCode
     * @dataProvider invalidBranchNameDataProvider
     * @test
     */
    public function invalidBranchNamesThrowException(string $type, string $branch, int $exceptionCode): void
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
