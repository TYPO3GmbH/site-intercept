<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Extractor;

use App\Exception\Composer\DocsComposerMissingValueException;
use App\Extractor\ComposerJson;
use PHPUnit\Framework\TestCase;

class ComposerJsonTest extends TestCase
{
    /**
     * @return array
     */
    public function attributesDataProvider(): array
    {
        return [
            ['name', 'foobar/baz'],
            ['type', 'foobar'],
            ['require', ['foobar/bark' => '^4.2']],
            ['authors', [['name' => 'Husel Pusel', 'email' => 'husel@example.com']]],
        ];
    }

    /**
     * @test
     */
    public function nameIsReturnedAsExpected(): void
    {
        $composerJson = new ComposerJson(['name' => 'foobar/baz']);
        $this->assertSame('foobar/baz', $composerJson->getName());
    }

    /**
     * @return array
     */
    public function emptyNameDataProvider(): array
    {
        return [
            [''],
            ['     '],
            [null],
        ];
    }

    /**
     * @test
     * @dataProvider emptyNameDataProvider
     * @param mixed $value
     */
    public function emptyNameThrowsException($value): void
    {
        $this->expectException(DocsComposerMissingValueException::class);
        $this->expectExceptionCode(1557309364);

        $composerJson = new ComposerJson(['name', $value]);
        $composerJson->getName();
    }

    /**
     * @test
     */
    public function typeIsReturnedAsExpected(): void
    {
        $composerJson = new ComposerJson(['type' => 'package']);
        $this->assertSame('package', $composerJson->getType());
    }

    /**
     * @return array
     */
    public function emptyTypeDataProvider(): array
    {
        return [
            [''],
            ['     '],
            [null],
        ];
    }

    /**
     * @test
     * @dataProvider emptyNameDataProvider
     * @param mixed $value
     */
    public function emptyTypeThrowsException($value): void
    {
        $this->expectException(DocsComposerMissingValueException::class);
        $this->expectExceptionCode(1557309364);

        $composerJson = new ComposerJson(['type', $value]);
        $composerJson->getType();
    }

    /**
     * @test
     */
    public function requirementsAreFoundCorrectly(): void
    {
        $composerJson = new ComposerJson(['require' => ['foobar/bark' => '^4.2']]);
        $this->assertTrue($composerJson->requires('foobar/bark'));
        $this->assertFalse($composerJson->requires('idonot/exist'));
    }

    /**
     * @test
     */
    public function emptyRequirementsDefinitionDoNotThrowException(): void
    {
        $composerJson = new ComposerJson(['require' => []]);
        $this->assertFalse($composerJson->requires('idonot/exist'));
    }

    /**
     * @test
     */
    public function missingRequirementsDefinitionDoNotThrowException(): void
    {
        $composerJson = new ComposerJson([]);
        $this->assertFalse($composerJson->requires('idonot/exist'));
    }

    /**
     * @test
     */
    public function emptyMinimumCmsCoreRequireThrowsException(): void
    {
        $composerJson = new ComposerJson([
            'name' => 'foobar/baz',
            'type' => 'typo3-cms-extension',
            'require' => ['foobar/bark' => '^4.2'],
            'authors' => [['name' => 'Husel Pusel', 'email' => 'husel@example.com']],
        ]);

        $this->expectException(DocsComposerMissingValueException::class);
        $this->expectExceptionCode(1558084137);
        $composerJson->getMinimumTypoVersion();
    }

    /**
     * @test
     */
    public function emptyMaximumCmsCoreRequireThrowsException(): void
    {
        $composerJson = new ComposerJson([
            'name' => 'foobar/baz',
            'type' => 'typo3-cms-extension',
            'require' => ['foobar/bark' => '^4.2'],
            'authors' => [['name' => 'Husel Pusel', 'email' => 'husel@example.com']],
        ]);

        $this->expectException(DocsComposerMissingValueException::class);
        $this->expectExceptionCode(1558084146);
        $composerJson->getMaximumTypoVersion();
    }

    /**
     * @param string $cmsCoreVersionString
     * @return array
     */
    public function dummyComposerJsonArray(string $cmsCoreVersionString): array
    {
        return [
            'name' => 'foobar/baz',
            'type' => 'typo3-cms-extension',
            'require' => ['foobar/bark' => '^4.2', 'typo3/cms-core' => $cmsCoreVersionString],
            'authors' => [['name' => 'Husel Pusel', 'email' => 'husel@example.com']],
        ];
    }

    /**
     * @return array
     */
    public function cmsCoreConstraintProvider(): array
    {
        return [
            ['^9.5', '9.5', '9.5'],
            ['^7.5.6 || ^8', '7.6', '8.7'],
            ['10.0.x-dev', '', ''],
            ['^9.5 || 10.4.*@dev', '9.5', '10.4'],
            ['~9.5', '9.5', '9.5'],
            ['^9', '9.5', '9.5'],
            ['~9.5.6', '9.5', '9.5'],
            ['^9.5.17 || ^10.4.2', '9.5', '10.4'],
            ['^10.0', '10.4', '10.4']
        ];
    }

    /**
     * @test
     * @dataProvider cmsCoreConstraintProvider
     * @param string $versionString
     * @param string $expectedMin
     * @param string $expectedMax
     */
    public function testCoreConstraints(string $versionString, string $expectedMin, string $expectedMax): void
    {
        $composerJson = new ComposerJson($this->dummyComposerJsonArray($versionString));

        $this->assertEquals($expectedMin, $composerJson->getMinimumTypoVersion());
        $this->assertEquals($expectedMax, $composerJson->getMaximumTypoVersion());
    }

    /**
     * @test
     */
    public function exceptionIsNotThrownForTypo3CmsCorePackage(): void
    {
        $composerJson = new ComposerJson([
            'name' => 'typo3/cms-core',
            'type' => 'typo3-cms-framework',
            'require' => ['foobar/bark' => '^4.2'],
            'authors' => [['name' => 'Husel Pusel', 'email' => 'husel@example.com']],
        ]);

        $this->assertEquals('', $composerJson->getMinimumTypoVersion());
        $this->assertEquals('', $composerJson->getMaximumTypoVersion());
    }
}
