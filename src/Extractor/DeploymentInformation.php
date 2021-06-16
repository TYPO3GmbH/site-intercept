<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

use App\Exception\ComposerJsonInvalidException;
use App\Exception\DocsPackageDoNotCareBranch;
use RuntimeException;

/**
 * Holds the environment information required for rendering and deployment of documentation jobs
 */
class DeploymentInformation
{
    private static array $typeMap = [
        'typo3-cms-documentation' => ['m' => 'manual'],
        'typo3-cms-framework' => ['c' => 'core-extension'],
        'typo3-cms-extension' => ['p' => 'extension'],
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
     * @var string Package vendor, eg. "georgringer"
     */
    public $vendor;

    /**
     * @var string Plain name of a package, e.g. "news"
     */
    public string $name;

    /**
     * @var string Full package name, eg. 'georgringer/news'
     */
    public string $packageName;

    /**
     * @var string Full package type, eg. 'typo3-cms-extension'
     */
    public string $packageType;

    /**
     * @var string Extension key, e.g. 'news'
     */
    public string $extensionKey;

    /**
     * @var string The (not changed) source branch or tag of the repository supposed to be checked out, eg. '1.2.3', '1.2', 'master', 'latest'
     */
    public string $sourceBranch;

    /**
     * @var string The target directory on the documentation server, typically identical to $branch, except for $branch='latest', this is deployed to 'master'
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
     * @var string Repository URL, eg. 'https://github.com/lolli42/enetcache/'
     */
    public string $repositoryUrl;

    /**
     * @var string public URL to the composer.json file in the repository
     */
    public string $publicComposerJsonUrl;

    /**
     * @var string Absolute path to the dump file, eg. '/.../var/docs-build-information/1893678543347'
     */
    public string $absoluteDumpFile;

    /**
     * @var string Path to dump file relative to document root, eg. 'docs-build-information/1893678543347'
     */
    public string $relativeDumpFile;

    /**
     * @var string TYPO3 version the package is compatible with (minimum)
     */
    public string $minimumTypoVersion;

    /**
     * @var string TYPO3 version the package is compatible with (maximum)
     */
    public string $maximumTypoVersion;

    /**
     * Constructor
     *
     * @param string $composerPackageName
     * @param string $composerPackageType
     * @param string $extensionKey
     * @param string $repositoryUrl
     * @param string $publicComposerJsonUrl
     * @param string $version
     * @param string $minimumTypoVersion,
     * @param string $maximumTypoVersion,
     * @param string $privateDir
     * @param string $subDir
     * @throws DocsPackageDoNotCareBranch
     * @throws ComposerJsonInvalidException
     */
    public function __construct(
        string $composerPackageName,
        string $composerPackageType,
        string $extensionKey,
        string $repositoryUrl,
        string $publicComposerJsonUrl,
        string $version,
        string $minimumTypoVersion,
        string $maximumTypoVersion,
        string $privateDir,
        string $subDir
    ) {
        $this->repositoryUrl = $repositoryUrl;
        $this->publicComposerJsonUrl = $publicComposerJsonUrl;
        $this->packageType = $composerPackageType;
        $packageName = $this->determinePackageName($composerPackageName);
        $packageType = $this->determinePackageType($composerPackageType, $composerPackageName);
        $this->extensionKey = $extensionKey;
        $this->vendor = key($packageName);
        $this->name = current($packageName);
        $this->packageName = $this->vendor . '/' . $this->name;
        $this->typeLong = current($packageType);
        $this->typeShort = key($packageType);
        $this->sourceBranch = $version;
        $this->targetBranchDirectory = $this->getTargetBranchDirectory($this->sourceBranch, $this->typeLong);
        $this->minimumTypoVersion = $minimumTypoVersion;
        $this->maximumTypoVersion = $maximumTypoVersion;

        $buildTime = ceil(microtime(true) * 10000);
        $this->absoluteDumpFile = implode('/', [
            $privateDir,
            $subDir,
            $buildTime,
        ]);
        $this->relativeDumpFile = implode('/', [ $subDir, $buildTime ]);
    }

    /**
     * @return array
     */
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
     * @param string $branch
     * @param string $type
     * @return string
     * @throws DocsPackageDoNotCareBranch
     * @throws RuntimeException
     */
    private function getTargetBranchDirectory(string $branch, string $type): string
    {
        $result = $branch;

        // 'master', 'latest' and 'main' become 'master'
        if ($result === 'latest' || $result === 'master' || $result === 'main') {
            return 'master';
        }

        // branch 'documentation-draft' becomes 'draft' (and is not indexed by spiders later)
        if ($result === 'documentation-draft') {
            return 'draft';
        }

        // Cut off a leading 'v', a tag like v8.7.2 will become 8.7.2
        $result = ltrim($result, 'v');

        if ($type === 'extension') {
            // Rules for extensions - verify structure '8.7.2' or 'v8.7.2'
            if (!preg_match('/^(\d+.\d+.\d+)$/', $result)) {
                throw new DocsPackageDoNotCareBranch(
                    'Branch / tag named \'' . $branch . '\' is ignored, only tags named \'major.minor.patch\' (eg. \'5.7.2\') are considered.',
                    1557498335
                );
            }
            // Remove patch level, '8.7.2' becomes '8.7'
            return implode('.', array_slice(explode('.', $result), 0, 2));
        }

        if ($type === 'core-extension' || $type === 'manual' || $type === 'other') {
            // Rules for manuals and core extensions - render branches like '8.5' as '8.5' and '8' as '8'
            $result = str_replace(['_', '-'], '.', $result);
            if (!preg_match('/^(\d+.\d+)$/', $result) && !preg_match('/^(\d+)$/', $result)) {
                throw new DocsPackageDoNotCareBranch(
                    'Branch / tag named \'' . $branch . '\' is ignored, only branches named \'major.minor\' or \'major\' (eg. \'5.7\') are considered.',
                    1557503542
                );
            }
            return $result;
        }

        // docs-home has only master branch, this is returned above already, safe to not handle this here.
        throw new RuntimeException('Unknown package type ' . $type);
    }

    /**
     * @param string $packageType
     * @param string $packageName
     * @throws ComposerJsonInvalidException
     * @return array
     */
    private function determinePackageType(string $packageType, string $packageName): array
    {
        if (array_key_exists($packageName, self::$packageTypeExceptionMap)) {
            return self::$packageTypeExceptionMap[$packageName];
        }

        if ($packageType === '') {
            throw new ComposerJsonInvalidException('composer.json \'type\' must be given', 1558019479);
        }

        if (!array_key_exists($packageType, self::$typeMap)) {
            throw new ComposerJsonInvalidException('composer.json \'type\' must be set to one of ' . implode(', ', array_keys(self::$typeMap)) . ', ' . $packageType . ' given', 1557490474);
        }

        return self::$typeMap[$packageType];
    }

    /**
     * @param string $packageName
     * @return array
     * @throws ComposerJsonInvalidException
     */
    private function determinePackageName(string $packageName): array
    {
        $packageName = trim($packageName);
        if ($packageName === '') {
            throw new ComposerJsonInvalidException('composer.json \'name\' must be given', 1558019290);
        }

        if (!preg_match('/^[\w-]+\/[\w-]+$/', $packageName)) {
            throw new ComposerJsonInvalidException('composer.json \'name\' must be of form \'vendor/package\', \'' . $packageName . '\' given.', 1553082490);
        }

        [$vendor, $name] = explode('/', $packageName);
        return [$vendor => $name];
    }
}
