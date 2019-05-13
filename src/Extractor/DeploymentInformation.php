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

/**
 * Holds the environment information required for rendering and deployment of documentation jobs
 */
class DeploymentInformation
{
    /**
     * @var array
     */
    private static $typeMap = [
        'typo3-cms-documentation' => ['m' => 'manual'],
        'typo3-cms-framework' => ['c' => 'core-extension'],
        'typo3-cms-extension' => ['p' => 'extension'],
        // There is a third one 'h' => 'docs-home', handled below.
    ];

    /**
     * @var string Package vendor, eg. "georgringer"
     */
    public $vendor;

    /**
     * @var string Plain name of a package, e.g. "news"
     */
    public $name;

    /**
     * @var string Full package name, eg. 'georgringer/news'
     */
    public $packageName;

    /**
     * @var string The (not changed) source branch or tag of the repository supposed to be checked out, eg. '1.2.3', '1.2', 'master', 'latest'
     */
    public $sourceBranch;

    /**
     * @var string The target directory on the documentation server, typically identical to $branch, except for $branch='latest', this is deployed to 'master'
     */
    public $targetBranchDirectory;

    /**
     * @var string Long type name of a composer package, e.g. "manual" or "core-extension"
     */
    public $typeLong;

    /**
     * @var string Short type name of a composer package, e.g. "m" or "p"
     */
    public $typeShort;

    /**
     * @var string Repository URL, eg. 'https://github.com/lolli42/enetcache/'
     */
    public $repositoryUrl;

    /**
     * @var string Absolute path to the dump file, eg. '/.../var/docs-build-information/1893678543347'
     */
    public $absoluteDumpFile;

    /**
     * @var string Path to dump file relative to document root, eg. 'docs-build-information/1893678543347'
     */
    public $relativeDumpFile;

    /**
     * Constructor
     *
     * @param ComposerJson $composerJson
     * @param PushEvent $pushEvent
     * @param string $privateDir
     * @param string $subDir
     * @throws ComposerJsonInvalidException
     * @throws DocsPackageDoNotCareBranch
     * @throws \RuntimeException
     */
    public function __construct(ComposerJson $composerJson, PushEvent $pushEvent, string $privateDir, string $subDir)
    {
        $this->repositoryUrl = $pushEvent->getRepositoryUrl();
        $packageName = $this->determinePackageName($composerJson);
        $packageType = $this->determinePackageType($composerJson, $this->repositoryUrl);

        $this->vendor = key($packageName);
        $this->name = current($packageName);
        $this->packageName = $this->vendor . '/' . $this->name;
        $this->typeLong = current($packageType);
        $this->typeShort = key($packageType);
        $this->sourceBranch = $pushEvent->getVersionString();
        $this->targetBranchDirectory = $this->getTargetBranchDirectory($this->sourceBranch, $this->typeLong);

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
            'vendor' => $this->vendor,
            'name' => $this->name,
            'package_name' => $this->packageName,
            'source_branch' => $this->sourceBranch,
            'target_branch_directory' => $this->targetBranchDirectory,
            'type_long' => $this->typeLong,
            'type_short' => $this->typeShort,
            // We don't need absoluteDumpFile and relativeDumpFile since these are given as variable to bamboo
        ];
    }

    /**
     * @return string
     */
    public function getVendor(): string
    {
        return $this->vendor;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTypeShort(): string
    {
        return $this->typeShort;
    }

    /**
     * @return string
     */
    public function getTypeShort(): string
    {
        return $this->typeShort;
    }

    /**
     * Determine the target directory this package with given branch/tag will be deployed to.
     *
     * @param string $branch
     * @param string $type
     * @return string
     * @throws DocsPackageDoNotCareBranch
     * @throws \RuntimeException
     */
    private function getTargetBranchDirectory(string $branch, string $type): string
    {
        $result = $branch;

        // 'master' and 'latest' become 'master'
        if ($result === 'latest' || $result === 'master') {
            return 'master';
        }

        return $branch;
    }

    /**
     * @param ComposerJson $composerJson
     * @param string $repositoryUrl
     * @return array
     * @throws ComposerJsonInvalidException
     */
    private function determinePackageType(ComposerJson $composerJson, string $repositoryUrl): array
    {
        if ($repositoryUrl === 'https://github.com/TYPO3-Documentation/DocsTypo3Org-Homepage.git') {
            // Hard coded final location for the docs homepage repository
            return [
                'h' => 'docs-home',
            ];
        }

        return self::$typeMap[$composerJson->getType()] ?? self::$typeMap['__default'];
    }

    /**
     * @param ComposerJson $composerJson
     * @return array
     * @throws ComposerJsonInvalidException
     */
    private function determinePackageName(ComposerJson $composerJson): array
    {
        if (!preg_match('/^[\w-]+\/[\w-]+$/', $composerJson->getName())) {
            throw new ComposerJsonInvalidException('composer.json \'name\' must be of form \'vendor/package\', \'' . $composerJson['name'] . '\' given.', 1553082490);
        }

        [$vendor, $name] = explode('/', $composerJson->getName());
        return [$vendor => $name];
    }
}
