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
        $this->assertSame('foobar/baz', $composerJson->getName());
    }

    public static function emptyNameDataProvider(): \Iterator
    {
        yield [''];
        yield ['     '];
        yield [null];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('emptyNameDataProvider')]
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
        $this->assertSame('typo3-cms-extension', $composerJson->getType());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('otherTypeDataProvider')]
    public function testTypeIsReturnedAsExpectedIfShouldBeOther($value): void
    {
        $composerJson = new ComposerJson(['name' => $value, 'type' => 'package']);
        $this->assertSame('other', $composerJson->getType());
    }

    public static function otherTypeDataProvider(): \Iterator
    {
        yield ['typo3/surf'];
        yield ['typo3/tailor'];
        yield [''];
        yield [null];
        yield ['     '];
    }

    public function testRequirementsAreFoundCorrectly(): void
    {
        $composerJson = new ComposerJson(['require' => ['foobar/bark' => '^4.2']]);
        $this->assertTrue($composerJson->requires('foobar/bark'));
        $this->assertFalse($composerJson->requires('idonot/exist'));
    }

    public function testEmptyRequirementsDefinitionDoNotThrowException(): void
    {
        $composerJson = new ComposerJson(['require' => []]);
        $this->assertFalse($composerJson->requires('idonot/exist'));
    }

    public function testMissingRequirementsDefinitionDoNotThrowException(): void
    {
        $composerJson = new ComposerJson([]);
        $this->assertFalse($composerJson->requires('idonot/exist'));
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

    public static function cmsCoreConstraintProvider(): \Iterator
    {
        yield ['^9.5', '9.5', '9.5'];
        yield ['^7.5.6 || ^8', '7.6', '8.7'];
        yield ['10.0.x-dev', '', ''];
        yield ['^9.5 || 10.4.*@dev', '9.5', '10.4'];
        yield ['~9.5', '9.5', '9.5'];
        yield ['^9', '9.5', '9.5'];
        yield ['~9.5.6', '9.5', '9.5'];
        yield ['^9.5.17 || ^10.4.2', '9.5', '10.4'];
        yield ['^10.0', '10.4', '10.4'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('cmsCoreConstraintProvider')]
    public function testCoreConstraints(string $versionString, string $expectedMin, string $expectedMax): void
    {
        $composerJson = new ComposerJson($this->dummyComposerJsonArray($versionString));

        $this->assertSame($expectedMin, $composerJson->getMinimumTypoVersion());
        $this->assertSame($expectedMax, $composerJson->getMaximumTypoVersion());
    }

    public function testExceptionIsNotThrownForTypo3CmsCorePackage(): void
    {
        $composerJson = new ComposerJson([
            'name' => 'typo3/cms-core',
            'type' => 'typo3-cms-framework',
            'require' => ['foobar/bark' => '^4.2'],
            'authors' => [['name' => 'Husel Pusel', 'email' => 'husel@example.com']],
        ]);

        $this->assertSame('', $composerJson->getMinimumTypoVersion());
        $this->assertSame('', $composerJson->getMaximumTypoVersion());
    }
}
