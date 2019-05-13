<?php
declare(strict_types=1);

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
}