<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Client\GeneralClient;
use App\Entity\DocumentationJar;
use App\Extractor\DeploymentInformation;
use App\Extractor\DocumentationBuildInformation;
use App\Extractor\GithubPushEventForDocs;
use App\Repository\DocumentationJarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class DocumentationBuildInformationService
{
    /**
     * @var array
     */
    private static $typeMap = [
        'typo3-cms-documentation' => ['m' => 'manual'],
        'typo3-cms-framework' => ['c' => 'core-extension'],
        'typo3-cms-extension' => ['p' => 'extension'],
        '__default' => ['p', 'package']
    ];

    /**
     * @var string
     */
    private $publicDir;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DocumentationJarRepository
     */
    private $documentationJarRepository;

    /**
     * Constructor
     *
     * @param string $publicDir
     * @param string $cacheDir
     * @param EntityManagerInterface $entityManager
     * @param Filesystem $fileSystem
     * @param LoggerInterface $logger
     */
    public function __construct(string $publicDir, string $cacheDir, EntityManagerInterface $entityManager, Filesystem $fileSystem, LoggerInterface $logger)
    {
        $this->publicDir = $publicDir;
        $this->cacheDir = $cacheDir;
        $this->entityManager = $entityManager;
        $this->fileSystem = $fileSystem;
        $this->logger = $logger;
        $this->documentationJarRepository = $this->entityManager->getRepository(DocumentationJar::class);
    }

    /**
     * @param GithubPushEventForDocs $pushEventForDocs
     * @return DocumentationBuildInformation
     * @throws \Exception
     */
    public function generateBuildInformation(GithubPushEventForDocs $pushEventForDocs): DocumentationBuildInformation
    {
        $buildTime = ceil(microtime(true) * 10000);
        $branchName = $this->normalizeBranchName($pushEventForDocs->tagOrBranchName);
        $deploymentInformation = $this->generateDeploymentInformation($pushEventForDocs->composerFile, $branchName);
        $this->assertBuildWasTriggeredByRepositoryOwner($deploymentInformation, $pushEventForDocs->repositoryUrl);

        $privateFilePath = implode('/', [
            $this->cacheDir,
            $deploymentInformation->getVendor(),
            $deploymentInformation->getName(),
            $deploymentInformation->getBranch(),
            'builds',
            $buildTime,
        ]);
        $relativePublicFilePath = 'builds/' . $buildTime;
        $absolutePublicFilePath = $this->publicDir . '/' . $relativePublicFilePath;

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $documentationJar = (new DocumentationJar())
                ->setRepositoryUrl($pushEventForDocs->repositoryUrl)
                ->setPackageName($deploymentInformation->getPackageName())
                ->setBranch($deploymentInformation->getBranch());
            $this->entityManager->persist($documentationJar);
            $this->entityManager->flush();

            if (!$this->fileSystem->exists($privateFilePath)) {
                // TODO: Move the string concatenation magic in an Encoder class?
                $fileContent = '#!/bin/bash' . PHP_EOL;
                foreach ($deploymentInformation->toArray() as $property => $value) {
                    $fileContent .= $property . '=' . $value . PHP_EOL;
                }

                $this->fileSystem->dumpFile($privateFilePath, $fileContent);
                $this->fileSystem->symlink($privateFilePath, $absolutePublicFilePath);
            }
            $this->entityManager->commit();
        } catch (\Exception $e) {
            if ($this->fileSystem->exists($privateFilePath)) {
                $this->fileSystem->remove($privateFilePath);
            }

            if ($this->fileSystem->exists($absolutePublicFilePath)) {
                $this->fileSystem->remove($absolutePublicFilePath);
            }
            $this->entityManager->getConnection()->rollBack();

            throw $e;
        }

        return new DocumentationBuildInformation($relativePublicFilePath);
    }

    /**
     * @param string $composerFilePath
     * @param string $branch
     * @return DeploymentInformation
     */
    private function generateDeploymentInformation(string $composerFilePath, string $branch): DeploymentInformation
    {
        $composerJson = json_decode($this->fetchRemoteFile($composerFilePath), true);
        $packageName = $this->determinePackageName($composerJson);
        $packageType = $this->determinePackageType($composerJson);

        $vendor = key($packageName);
        $name = current($packageName);
        $longPackageType = current($packageType);
        $shortPackageType = key($packageType);

        return new DeploymentInformation($vendor, $name, $branch, $longPackageType, $shortPackageType);
    }

    /**
     * @param DeploymentInformation $deploymentInformation
     * @param string $repositoryUrl
     */
    private function assertBuildWasTriggeredByRepositoryOwner(DeploymentInformation $deploymentInformation, string $repositoryUrl): void
    {
        $record = $this->documentationJarRepository->findOneBy([
            'package_name' => $deploymentInformation->getPackageName()
        ]);

        if ($record instanceof DocumentationJar && $record->getRepositoryUrl() !== $repositoryUrl) {
            throw new \RuntimeException(
                'Build was triggered by ' . $repositoryUrl . ' which seems to be a fork of ' . $record->getRepositoryUrl(),
                1553090750
            );
        }
    }

    /**
     * @param string $path
     * @return string
     */
    private function fetchRemoteFile(string $path): string
    {
        $response = (new GeneralClient())->request('GET', $path);
        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            throw new IOException('Could not read remote file ' . $path . ', received status code ' . $statusCode, 1553081065);
        }

        return $response->getBody()->getContents();
    }

    /**
     * Check whether given version matches expected format and remove patch level from version
     *
     * @param string $version
     * @return string
     */
    private function normalizeBranchName(string $version): string
    {
        if ($version === 'latest') {
            // TODO: For the time being the version "latest" is mapped to "master"
            $version = 'master';
        }

        if (!preg_match('/^master|(?:v?\d+.\d+.\d+)$/', $version)) {
            throw new \InvalidArgumentException('Invalid version format given, expected either "latest" "master" or \d.\d.\d.');
        }

        $version = ltrim($version, 'v');
        return implode('.', array_slice(explode('.', $version), 0, 2));
    }

    /**
     * @param array $composerJson
     * @return array
     * @throws \InvalidArgumentException
     */
    private function determinePackageType(array $composerJson): array
    {
        if (empty($composerJson['type'])) {
            throw new \InvalidArgumentException('No package type defined in composer.json', 1553081747);
        }

        if (isset(self::$typeMap[$composerJson['type']])) {
            return self::$typeMap[$composerJson['type']];
        }

        $this->logger->info('Received unmapped package type "' . $composerJson['type'] . '" as defined in composer.json, falling back to default');
        return self::$typeMap['__default'];
    }

    /**
     * @param array $composerJson
     * @return array
     */
    private function determinePackageName(array $composerJson): array
    {
        if (empty($composerJson['name'])) {
            throw new \InvalidArgumentException('No package name defined in composer.json', 1553082362);
        }

        if (!preg_match('/^[\w-]+\/[\w-]+$/', $composerJson['name'])) {
            throw new \InvalidArgumentException('Invalid package name ' . $composerJson['name'] . ' provided', 1553082490);
        }

        [$vendor, $name] = explode('/', $composerJson['name']);
        return [$vendor => $name];
    }
}
