<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

use App\Exception\Composer\MissingValueException;

/**
 * Contains contents of the composer.json
 */
class ComposerJson
{
    /**
     * @var array
     */
    private $composerJson;

    public function __construct(array $composerJson)
    {
        $this->composerJson = $composerJson;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        $this->assertPropertyContainsValue('name');
        return (string)$this->composerJson['name'];
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        $this->assertPropertyContainsValue('type');
        return (string)$this->composerJson['type'];
    }

    /**
     * @param string $packageName
     * @return bool
     */
    public function requires(string $packageName): bool
    {
        return isset($this->composerJson['require'][$packageName]);
    }

    /**
     * @return array
     */
    public function getFirstAuthor(): array
    {
        $this->assertPropertyContainsValue('authors');
        return current($this->composerJson['authors']);
    }

    /**
     * @param string $propertyName
     * @throws MissingValueException
     */
    private function assertPropertyContainsValue(string $propertyName): void
    {
        if (empty($this->composerJson[$propertyName])
            || (is_string($this->composerJson[$propertyName]) && trim($this->composerJson[$propertyName]) === '')
        ) {
            throw new MissingValueException('Property "' . $propertyName . '"" is missing or is empty in composer.json', 1557309364);
        }
    }
}
