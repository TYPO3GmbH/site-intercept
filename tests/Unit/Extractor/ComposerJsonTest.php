<?php
declare(strict_types=1);

namespace App\Tests\Unit\Extractor;

use App\Exception\Composer\DocsComposerMissingValueException;
use App\Exception\ComposerJsonInvalidException;
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
    public function cmsCoreConstraintTestDataProvider(string $cmsCoreVersionString): array
    {
        return [
            'name' => 'foobar/baz',
            'type' => 'typo3-cms-extension',
            'require' => ['foobar/bark' => '^4.2', 'typo3/cms-core' => $cmsCoreVersionString],
            'authors' => [['name' => 'Husel Pusel', 'email' => 'husel@example.com']],
        ];
    }

    /**
     * @test
     */
    public function maximumCmsCoreVersionIsReturnedAsExpectedWithOneVersion(): void
    {
        $composerJson = new ComposerJson($this->cmsCoreConstraintTestDataProvider('^9.5'));

        $this->assertEquals('9.5', $composerJson->getMaximumTypoVersion());
    }

    /**
     * @test
     */
    public function maximumCmsCoreVersionIsReturnedAsExpectedWithTwoVersions(): void
    {
        $composerJson = new ComposerJson($this->cmsCoreConstraintTestDataProvider('8.7.*, <= 9.5.*'));

        $this->assertEquals('9.5', $composerJson->getMaximumTypoVersion());
    }

    /**
     * @test
     */
    public function maximumCmsCoreVersionIsReturnedAsExpectedWithSmallRange(): void
    {
        $composerJson = new ComposerJson($this->cmsCoreConstraintTestDataProvider('^7.5.6 || ^8'));

        $this->assertEquals('8', $composerJson->getMaximumTypoVersion());
    }

    /**
     * @test
     */
    public function maximumCmsCoreVersionIsReturnedAsExpectedWithBigRange(): void
    {
        $composerJson = new ComposerJson($this->cmsCoreConstraintTestDataProvider('2.7.44 || 2.8.37 || 3.4.7 || 4.0.7'));

        $this->assertEquals('4.0', $composerJson->getMaximumTypoVersion());
    }

    /**
     * @test
     */
    public function minimumCmsCoreVersionIsReturnedAsExpectedWithOneVersion(): void
    {
        $composerJson = new ComposerJson($this->cmsCoreConstraintTestDataProvider('^9.5'));

        $this->assertEquals('9.5', $composerJson->getMinimumTypoVersion());
    }

    /**
     * @test
     */
    public function minimumCmsCoreVersionIsReturnedAsExpectedWithTwoVersions(): void
    {
        $composerJson = new ComposerJson($this->cmsCoreConstraintTestDataProvider('8.7.*, <= 9.5.*'));

        $this->assertEquals('8.7', $composerJson->getMinimumTypoVersion());
    }

    /**
     * @test
     */
    public function minimumCmsCoreVersionIsReturnedAsExpectedWithSmallRange(): void
    {
        $composerJson = new ComposerJson($this->cmsCoreConstraintTestDataProvider('^7.5.6 || ^8'));

        $this->assertEquals('7.5', $composerJson->getMinimumTypoVersion());
    }

    /**
     * @test
     */
    public function minimumCmsCoreVersionIsReturnedAsExpectedWithBigRange(): void
    {
        $composerJson = new ComposerJson($this->cmsCoreConstraintTestDataProvider('2.7.44 || 2.8.37 || 3.4.7 || 4.0.7'));

        $this->assertEquals('2.7', $composerJson->getMinimumTypoVersion());
    }

    /**
     * @test
     */
    public function exceptionIsNotThrownWhenCmsCoreVersionNotPresentInNonExtensionPackage(): void
    {
        $composerJson = new ComposerJson([
            'name' => 'foobar/baz',
            'type' => 'not-a-typo3-cms-extension',
            'require' => ['foobar/bark' => '^4.2'],
            'authors' => [['name' => 'Husel Pusel', 'email' => 'husel@example.com']],
        ]);

        $this->assertEquals('', $composerJson->getMinimumTypoVersion());
        $this->assertEquals('', $composerJson->getMaximumTypoVersion());
    }
}