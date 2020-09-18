<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

use App\Exception\Composer\DocsComposerMissingValueException;
use Composer\Semver\Semver;

/**
 * Contains contents of the composer.json
 */
class ComposerJson
{
    private const ALLOWED_TYPO3_VERSIONS = ['6.2', '7.6', '8.7', '9.5', '10.4'];

    private array $composerJson;

    public function __construct(array $composerJson)
    {
        $this->composerJson = $composerJson;
    }

    /**
     * @return string
     * @throws DocsComposerMissingValueException
     */
    public function getName(): string
    {
        $this->assertPropertyContainsValue('name');
        return (string)$this->composerJson['name'];
    }

    /**
     * @return string
     * @throws DocsComposerMissingValueException
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
     * @return string|null
     */
    public function getCoreRequirement(): ?string
    {
        return $this->composerJson['require']['typo3/cms-core'] ?? $this->composerJson['require']['typo3/cms'] ?? null;
    }

    /**
     * @return array
     * @throws DocsComposerMissingValueException
     */
    public function getFirstAuthor(): array
    {
        $this->assertPropertyContainsValue('authors');
        return current($this->composerJson['authors']);
    }

    /**
     * @return string
     * @throws DocsComposerMissingValueException
     */
    public function getMinimumTypoVersion(): string
    {
        // Leave version constraint empty for typo3/cms-core itself
        if ($this->getName() === 'typo3/cms-core') {
            return '';
        }
        if ($this->getCoreRequirement() === null) {
            throw new DocsComposerMissingValueException('typo3/cms-core must be required in the composer json, but was not found', 1558084137);
        }

        return $this->extractTypoVersion();
    }

    /**
     * @return string
     * @throws DocsComposerMissingValueException
     */
    public function getMaximumTypoVersion(): string
    {
        // Leave version constraint empty for typo3/cms-core itself
        if ($this->getName() === 'typo3/cms-core') {
            return '';
        }
        if ($this->getCoreRequirement() === null) {
            throw new DocsComposerMissingValueException('typo3/cms-core must be required in the composer json, but was not found', 1558084146);
        }

        return $this->extractTypoVersion(true);
    }

    /**
     * @param bool $getMaximum
     * @return string
     */
    private function extractTypoVersion(bool $getMaximum = false): string
    {
        $maxVersion = '';
        foreach (self::ALLOWED_TYPO3_VERSIONS as $typoVersion) {
            if (Semver::satisfies($typoVersion . '.999', $this->getCoreRequirement())) {
                if (!$getMaximum) {
                    return $typoVersion;
                }
                $maxVersion = $typoVersion;
            }
        }

        return $maxVersion;
    }

    /**
     * Tries to determine the extension key based on package's composer.json
     *
     * @return null|string
     */
    public function getExtensionKey(): ?string
    {
        if (strpos($this->getType(), 'typo3-cms-') === false) {
            return null;
        }

        if (!empty($this->composerJson['extra']['typo3/cms']['extension-key'])) {
            return $this->composerJson['extra']['typo3/cms']['extension-key'];
        }

        foreach (array_keys($this->composerJson['replace'] ?? []) as $packageName) {
            if (strpos($packageName, '/') === false) {
                return trim($packageName);
            }
        }

        [, $extensionKey] = explode('/', $this->getName(), 2);
        return str_replace('-', '_', $extensionKey);
    }

    /**
     * @param string $propertyName
     * @throws DocsComposerMissingValueException
     */
    private function assertPropertyContainsValue(string $propertyName): void
    {
        if (empty($this->composerJson[$propertyName])
            || (is_string($this->composerJson[$propertyName]) && trim($this->composerJson[$propertyName]) === '')
        ) {
            throw new DocsComposerMissingValueException('Property "' . $propertyName . '" is missing or is empty in composer.json', 1557309364);
        }
    }
}
