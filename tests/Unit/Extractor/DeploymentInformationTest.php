<?php
declare(strict_types=1);

namespace App\Tests\Unit\Extractor;

use App\Exception\Composer\MissingValueException;
use App\Extractor\ComposerJson;
use App\Extractor\DeploymentInformation;
use PHPUnit\Framework\TestCase;

class DeploymentInformationTest extends TestCase
{
    /**
     * @test
     */
    public function composerJsonSetsValuesAsExpected(): void
    {
        $branch = 'master';
        $composerJsonAsArray = [
            'name' => 'foobar/bazfnord',
            'type' => 'typo3-cms-framework',
        ];

        $subject = new DeploymentInformation(new ComposerJson($composerJsonAsArray), $branch);

        $this->assertSame('foobar', $subject->getVendor());
        $this->assertSame('bazfnord', $subject->getName());
        $this->assertSame('foobar/bazfnord', $subject->getPackageName());
        $this->assertSame('master', $subject->getBranch());
        $this->assertSame('c', $subject->getTypeShort());
        $this->assertSame('core-extension', $subject->getTypeLong());
    }

    /**
     * @test
     */
    public function arrayIsReturnedAsExpected(): void
    {
        $branch = 'master';
        $composerJsonAsArray = [
            'name' => 'foobar/bazfnord',
            'type' => 'typo3-cms-framework',
        ];

        $expected = [
            'vendor' => 'foobar',
            'name' => 'bazfnord',
            'branch' => $branch,
            'target_branch_directory' => $branch,
            'type_long' => 'core-extension',
            'type_short' => 'c',
        ];
        $this->assertSame($expected, (new DeploymentInformation(new ComposerJson($composerJsonAsArray), $branch))->toArray());
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
        $branch = 'master';
        $composerJsonAsArray = [
            'name' => $packageName,
            'type' => 'foo',
        ];

        $subject = new DeploymentInformation(new ComposerJson($composerJsonAsArray), $branch);
        $this->assertSame($expectedVendor, $subject->getVendor());
        $this->assertSame($expectedName, $subject->getName());
        $this->assertSame($packageName, $subject->getPackageName());
    }

    /**
     * @return array
     */
    public function invalidPackageNameDataProvider(): array
    {
        return [
            ['', MissingValueException::class, 1557309364],
            [null, MissingValueException::class, 1557309364],
            ['baz', \InvalidArgumentException::class, 1553082490],
            ['3245345', \InvalidArgumentException::class, 1553082490],
            ['husel_pusel:foobar', \InvalidArgumentException::class, 1553082490],
        ];
    }

    /**
     * @param string $packageName
     * @param string $expectedException
     * @param int $expectedExceptionCode
     * @dataProvider invalidPackageNameDataProvider
     * @test
     */
    public function invalidPackageNameThrowException(?string $packageName, string $expectedException, int $expectedExceptionCode): void
    {
        $this->expectException($expectedException);
        $this->expectExceptionCode($expectedExceptionCode);

        $composerJsonAsArray = [
            'name' => $packageName,
            'type' => 'foo',
        ];
        new DeploymentInformation(new ComposerJson($composerJsonAsArray), 'master');
    }

    /**
     * @return array
     */
    public function packageTypeDataProvider(): array
    {
        return [
            'manual' => ['typo3-cms-documentation', 'manual', 'm'],
            'core' => ['typo3-cms-framework', 'core-extension', 'c'],
            'extension' => ['typo3-cms-extension', 'extension', 'p'],
            'anything else' => ['husel', 'package', 'p'],
        ];
    }

    /**
     * @param string $type
     * @param string $expectedLong
     * @param string $expectedShort
     * @dataProvider packageTypeDataProvider
     * @test
     */
    public function packageTypePartsAreCorrectlyResolved(string $type, string $expectedLong, string $expectedShort): void
    {
        $branch = 'master';
        $composerJsonAsArray = ['name' => 'foobar/bazfnord'];
        if (isset($type)) {
            $composerJsonAsArray['type'] = $type;
        }

        $subject = new DeploymentInformation(new ComposerJson($composerJsonAsArray), $branch);
        $this->assertSame($expectedLong, $subject->getTypeLong());
        $this->assertSame($expectedShort, $subject->getTypeShort());
    }

    /**
     * @return array
     */
    public function invalidPackageTypeDataProvider(): array
    {
        return [
            'empty string' => [''],
            'nothing set' => [null],
        ];
    }

    /**
     * @param string $type
     * @dataProvider invalidPackageTypeDataProvider
     * @test
     */
    public function invalidPackageTypesThrowException(?string $type): void
    {
        $this->expectException(MissingValueException::class);
        $this->expectExceptionCode(1557309364);

        $composerJsonAsArray = ['name' => 'foobar/bazfnord'];
        if (isset($type)) {
            $composerJsonAsArray['type'] = $type;
        }

        new DeploymentInformation(new ComposerJson($composerJsonAsArray), 'master');
    }

    /**
     * @return array
     */
    public function validBranchNameDataProvider(): array
    {
        return [
            ['master', 'master'],
            ['latest', 'latest'],
            ['1.2.3', '1.2'],
            ['v1.5.8', '1.5'],
        ];
    }

    /**
     * @param string $branch
     * @param string $expectedBranch
     * @dataProvider validBranchNameDataProvider
     * @test
     */
    public function branchNamesAreNormalized(string $branch, string $expectedBranch): void
    {
        $composerJsonAsArray = [
            'name' => 'foobar/bazfnord',
            'type' => 'foo',
        ];

        $subject = new DeploymentInformation(new ComposerJson($composerJsonAsArray), $branch);
        $this->assertSame($expectedBranch, $subject->getBranch());
    }

    /**
     * @return array
     */
    public function validTargetBranchDirectoryDataProvider(): array
    {
        return [
            ['master', 'master'],
            ['latest', 'master'],
            ['1.2.3', '1.2'],
            ['v1.5.8', '1.5'],
        ];
    }

    /**
     * @param string $branch
     * @param string $expectedBranch
     * @dataProvider validTargetBranchDirectoryDataProvider
     * @test
     */
    public function targetBranchDirectoriesNormalized(string $branch, string $expectedBranch): void
    {
        $composerJsonAsArray = [
            'name' => 'foobar/bazfnord',
            'type' => 'foo',
        ];
        $subject = new DeploymentInformation(new ComposerJson($composerJsonAsArray), $branch);
        $this->assertSame($expectedBranch, $subject->getTargetBranchDirectory());
    }

    /**
     * @return array
     */
    public function invalidBranchNameDataProvider(): array
    {
        return [
            [''],
            ['1.2.3.4'],
            ['v1.5.8.3.3'],
            ['sdfoisdufso8iufd'],
            ['1.2.3-dev'],
            ['1.2..3'],
        ];
    }

    /**
     * @param string $branch
     * @dataProvider invalidBranchNameDataProvider
     * @test
     */
    public function invalidBranchNamesThrowException(string $branch): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1553257961);

        $composerJsonAsArray = [
            'name' => 'foobar/bazfnord',
            'type' => 'foo',
        ];

        new DeploymentInformation(new ComposerJson($composerJsonAsArray), $branch);
    }
}