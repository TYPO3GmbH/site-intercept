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
use App\Exception\ComposerJsonInvalidException;

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
     * @return string
     * @throws ComposerJsonInvalidException
     */
    public function getMinimumTypoVersion(): string
    {
        $typoVersion = $this->extractTypoVersion();

        if (!preg_match('/\d\.\d/', $typoVersion) && $this->getType() === 'typo3-cms-extension') {
            throw new ComposerJsonInvalidException();
        }

        return $typoVersion;
    }

    /**
     * @return string
     * @throws ComposerJsonInvalidException
     */
    public function getMaximumTypoVersion(): string
    {
        $typoVersion = $this->extractTypoVersion(true);

        if (!preg_match('/\d\.\d/', $typoVersion) && $this->getType() === 'typo3-cms-extension') {
            throw new ComposerJsonInvalidException();
        }

        return $typoVersion;
    }

    /**
     * @param bool $getMaximum
     * @return string
     */
    private function extractTypoVersion(bool $getMaximum = false): string
    {
        $typoVersion = '';

        if (isset($this->composerJson['require']['typo3/cms-core'])) {
            $typoVersion = $this->composerJson['require']['typo3/cms-core'];
            if (strpos($typoVersion, ',') !== false) {
                $typoVersion = explode(',', $typoVersion)[(int)$getMaximum];
            }
            $typoVersion = str_replace(['^', '~', '.*', '>=', '<='], '', $typoVersion);
        }

        return trim($typoVersion);
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
