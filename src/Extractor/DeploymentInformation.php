<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

use App\Exception\ComposerJsonInvalidException;
use App\Exception\DocsPackageDoNotCareBranch;

/**
 * Holds the environment information required for rendering and deployment of documentation jobs.
 */
class DeploymentInformation
{
    private static array $typeMap = [
        'typo3-cms-documentation' => ['m' => 'manual'],
        'typo3-cms-framework' => ['c' => 'core-extension'],
        'typo3-cms-extension' => ['p' => 'extension'],
        'other' => ['other' => 'other'],
        '' => ['other' => 'other'],
        // There is a third one 'h' => 'docs-home', handled below.
        // There is a fourth one 'other' => 'other', handled below.
    ];
    private static array $packageTypeExceptionMap = [
        'typo3/docs-homepage' => ['h' => 'docs-home'],
        'typo3/view-helper-reference' => ['other' => 'other'],
        'typo3/surf' => ['other' => 'other'],
        'typo3/tailor' => ['other' => 'other'],
    ];

    /**
     * @var string Package vendor, e.g. "georgringer"
     */
    public $vendor;

    /**
     * @var string Plain name of a package, e.g. "news"
     */
    public string $name;

    /**
     * @var string Full package name, e.g. 'georgringer/news'
     */
    public string $packageName;

    /**
     * @var string Extension key, e.g. 'news'
     */
    public string $extensionKey;

    /**
     * @var string The target directory on the documentation server, typically identical to, except for='latest', this is deployed to 'master'
     */
    public string $targetBranchDirectory;

    /**
     * @var string Long type name of a composer package, e.g. "manual" or "core-extension"
     */
    public string $typeLong;

    /**
     * @var string Short type name of a composer package, e.g. "m" or "p"
     */
    public $typeShort;

    /**
     * @var string Absolute path to the dump file, e.g. '/.../var/docs-build-information/1893678543347'
     */
    public string $absoluteDumpFile;

    /**
     * @var string Path to dump file relative to document root, e.g. 'docs-build-information/1893678543347'
     */
    public string $relativeDumpFile;

    /**
     * Constructor.
     *
     * @param string $extensionKey
     * @param string $minimumTypoVersion,
     * @param string $maximumTypoVersion,
     *
     * @throws DocsPackageDoNotCareBranch
     * @throws ComposerJsonInvalidException
     */
    public function __construct(
        string $composerPackageName,
        public string $packageType,
        ?string $extensionKey,
        public string $repositoryUrl,
        public string $publicComposerJsonUrl,
        public string $sourceBranch,
        public string $minimumTypoVersion,
        public string $maximumTypoVersion,
        string $privateDir,
        string $subDir
    ) {
        $packageName = $this->determinePackageName($composerPackageName);
        $packageType = $this->determinePackageType($packageType, $composerPackageName);
        $this->extensionKey = $extensionKey ?? '';
        $this->vendor = key($packageName);
        $this->name = current($packageName);
        $this->packageName = $this->vendor . '/' . $this->name;
        $this->typeLong = current($packageType);
        $this->typeShort = key($packageType);
        $this->targetBranchDirectory = $this->getTargetBranchDirectory($this->sourceBranch, $this->typeLong);

        $buildTime = ceil(microtime(true) * 10000);
        $this->absoluteDumpFile = implode('/', [
            $privateDir,
            $subDir,
            $buildTime,
        ]);
        $this->relativeDumpFile = implode('/', [$subDir, $buildTime]);
    }

    public function toArray(): array
    {
        return [
            'repository_url' => $this->repositoryUrl,
            'public_composer_json_url' => $this->publicComposerJsonUrl,
            'vendor' => $this->vendor,
            'name' => $this->name,
            'package_name' => $this->packageName,
            'package_type' => $this->packageType,
            'extension_key' => $this->extensionKey,
            'source_branch' => $this->sourceBranch,
            'target_branch_directory' => $this->targetBranchDirectory,
            'type_long' => $this->typeLong,
            'type_short' => $this->typeShort,
            // We don't need absoluteDumpFile and relativeDumpFile since these are given as variable to bamboo
        ];
    }

    /**
     * Determine the target directory this package with given branch/tag will be deployed to.
     *
     * @throws DocsPackageDoNotCareBranch
     * @throws \RuntimeException
     */
    private function getTargetBranchDirectory(string $branch, string $type): string
    {
        $result = $branch;

        // 'master', 'latest' and 'main' become 'main'
        if ('latest' === $result || 'master' === $result || 'main' === $result) {
            return 'main';
        }

        // branch 'documentation-draft' becomes 'draft' (and is not indexed by spiders later)
        if ('documentation-draft' === $result) {
            return 'draft';
        }

        // Cut off a leading 'v', a tag like v8.7.2 will become 8.7.2
        $result = ltrim($result, 'v');

        if ('extension' === $type) {
            // Rules for extensions - verify structure '8.7.2' or 'v8.7.2'
            if (!preg_match('/^(\d+.\d+.\d+)$/', $result)) {
                throw new DocsPackageDoNotCareBranch('Branch / tag named \'' . $branch . '\' is ignored, only tags named \'major.minor.patch\' (e.g. \'5.7.2\') are considered.', 1557498335);
            }

            // Remove patch level, '8.7.2' becomes '8.7'
            return implode('.', array_slice(explode('.', $result), 0, 2));
        }

        if ('core-extension' === $type || 'manual' === $type || 'other' === $type) {
            // Rules for manuals and core extensions - render branches like '8.5' as '8.5' and '8' as '8'
            $result = str_replace(['_', '-'], '.', $result);
            if (preg_match('/^v?((?<derivedBranchName>\d+.\d+).\d+)$/', $result, $matches)) {
                $result = $matches['derivedBranchName'];
            }
            if (!preg_match('/^(\d+.\d+)$/', $result) && !preg_match('/^(\d+)$/', $result)) {
                throw new DocsPackageDoNotCareBranch('Branch / tag named \'' . $branch . '\' is ignored, only branches named \'major.minor\' or \'major\' (e.g. \'5.7\') are considered.', 1557503542);
            }

            return $result;
        }

        // docs-home has only main branch, this is returned above already, safe to not handle this here.
        throw new \RuntimeException('Unknown package type ' . $type);
    }

    /**
     * @throws ComposerJsonInvalidException
     */
    private function determinePackageType(string $packageType, string $packageName): array
    {
        if (array_key_exists($packageName, self::$packageTypeExceptionMap)) {
            return self::$packageTypeExceptionMap[$packageName];
        }

        if (!array_key_exists($packageType, self::$typeMap)) {
            throw new ComposerJsonInvalidException('composer.json \'type\' must be set to one of ' . implode(', ', array_keys(self::$typeMap)) . ', ' . $packageType . ' given', 1557490474);
        }

        return self::$typeMap[$packageType];
    }

    /**
     * @throws ComposerJsonInvalidException
     */
    private function determinePackageName(string $packageName): array
    {
        $packageName = trim($packageName);
        if ('' === $packageName) {
            throw new ComposerJsonInvalidException('composer.json \'name\' must be given', 1558019290);
        }

        if (!preg_match('/^[\w-]+\/[\w-]+$/', $packageName)) {
            throw new ComposerJsonInvalidException('composer.json \'name\' must be of form \'vendor/package\', \'' . $packageName . '\' given.', 1553082490);
        }

        [$vendor, $name] = explode('/', $packageName);

        return [$vendor => $name];
    }
}
