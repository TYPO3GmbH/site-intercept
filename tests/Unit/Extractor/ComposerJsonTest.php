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
    public static function attributesDataProvider(): array
    {
        return [
            ['name', 'foobar/baz'],
            ['type', 'foobar'],
            ['require', ['foobar/bark' => '^4.2']],
            ['authors', [['name' => 'Husel Pusel', 'email' => 'husel@example.com']]],
        ];
    }

    public function testNameIsReturnedAsExpected(): void
    {
        $composerJson = new ComposerJson(['name' => 'foobar/baz']);
        self::assertSame('foobar/baz', $composerJson->getName());
    }

    public static function emptyNameDataProvider(): array
    {
        return [
            [''],
            ['     '],
            [null],
        ];
    }

    /**
     * @dataProvider emptyNameDataProvider
     */
    public function testEmptyNameThrowsException(mixed $value): void
    {
        $this->expectException(DocsComposerMissingValueException::class);
        $this->expectExceptionCode(1557309364);

        $composerJson = new ComposerJson(['name', $value]);
        $composerJson->getName();
    }

    public function testTypeIsReturnedAsExpected(): void
    {
        $composerJson = new ComposerJson(['name' => '123', 'type' => 'typo3-cms-extension']);
        self::assertSame('typo3-cms-extension', $composerJson->getType());
    }

    /**
     * @dataProvider otherTypeDataProvider
     */
    public function testTypeIsReturnedAsExpectedIfShouldBeOther($value): void
    {
        $composerJson = new ComposerJson(['name' => $value, 'type' => 'package']);
        self::assertSame('other', $composerJson->getType());
    }

    public static function otherTypeDataProvider(): array
    {
        return [
            ['typo3/surf'],
            ['typo3/tailor'],
            [''],
            [null],
            ['     '],
        ];
    }

    public function testRequirementsAreFoundCorrectly(): void
    {
        $composerJson = new ComposerJson(['require' => ['foobar/bark' => '^4.2']]);
        self::assertTrue($composerJson->requires('foobar/bark'));
        self::assertFalse($composerJson->requires('idonot/exist'));
    }

    public function testEmptyRequirementsDefinitionDoNotThrowException(): void
    {
        $composerJson = new ComposerJson(['require' => []]);
        self::assertFalse($composerJson->requires('idonot/exist'));
    }

    public function testMissingRequirementsDefinitionDoNotThrowException(): void
    {
        $composerJson = new ComposerJson([]);
        self::assertFalse($composerJson->requires('idonot/exist'));
    }

    public function testEmptyMinimumCmsCoreRequireThrowsException(): void
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

    public function testEmptyMaximumCmsCoreRequireThrowsException(): void
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

    public static function dummyComposerJsonArray(string $cmsCoreVersionString): array
    {
        return [
            'name' => 'foobar/baz',
            'type' => 'typo3-cms-extension',
            'require' => ['foobar/bark' => '^4.2', 'typo3/cms-core' => $cmsCoreVersionString],
            'authors' => [['name' => 'Husel Pusel', 'email' => 'husel@example.com']],
        ];
    }

    public static function cmsCoreConstraintProvider(): array
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
            ['^10.0', '10.4', '10.4'],
        ];
    }

    /**
     * @dataProvider cmsCoreConstraintProvider
     */
    public function testCoreConstraints(string $versionString, string $expectedMin, string $expectedMax): void
    {
        $composerJson = new ComposerJson($this->dummyComposerJsonArray($versionString));

        self::assertEquals($expectedMin, $composerJson->getMinimumTypoVersion());
        self::assertEquals($expectedMax, $composerJson->getMaximumTypoVersion());
    }

    public function testExceptionIsNotThrownForTypo3CmsCorePackage(): void
    {
        $composerJson = new ComposerJson([
            'name' => 'typo3/cms-core',
            'type' => 'typo3-cms-framework',
            'require' => ['foobar/bark' => '^4.2'],
            'authors' => [['name' => 'Husel Pusel', 'email' => 'husel@example.com']],
        ]);

        self::assertEquals('', $composerJson->getMinimumTypoVersion());
        self::assertEquals('', $composerJson->getMaximumTypoVersion());
    }
}
