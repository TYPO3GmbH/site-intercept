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
use App\Enum\DocumentationStatus;
use App\Exception\Composer\DocsComposerDependencyException;
use App\Exception\ComposerJsonInvalidException;
use App\Exception\ComposerJsonNotFoundException;
use App\Exception\DocsPackageRegisteredWithDifferentRepositoryException;
use App\Extractor\ComposerJson;
use App\Extractor\DeploymentInformation;
use App\Extractor\PushEvent;
use App\Repository\DocumentationJarRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * This service class generates a `source`-able file that contains some deployment-related
 * environment variables based on the push event it receives.
 */
class DocumentationBuildInformationService
{
    /**
     * @var string Absolute, private base directory where deployment infos are stored, configured via DI, typically '/.../var/'
     */
    private $privateDir;

    /**
     * @var string Name of sub directory in $publicDir and $privateDir where the files are stored, typically 'docs-build-information'
     */
    private $subDir;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var DocumentationJarRepository
     */
    private $documentationJarRepository;

    /**
     * @var GeneralClient
     */
    private $client;

    /**
     * Constructor
     *
     * @param string $privateDir
     * @param string $subDir
     * @param EntityManagerInterface $entityManager
     * @param Filesystem $fileSystem
     * @param GeneralClient $client
     */
    public function __construct(
        string $privateDir,
        string $subDir,
        EntityManagerInterface $entityManager,
        Filesystem $fileSystem,
        GeneralClient $client
    ) {
        $this->privateDir = $privateDir;
        $this->subDir = $subDir;
        $this->entityManager = $entityManager;
        $this->fileSystem = $fileSystem;
        $this->documentationJarRepository = $this->entityManager->getRepository(DocumentationJar::class);
        $this->client = $client;
    }

    /**
     * Fetch composer.json from a remote repository to get more package information.
     *
     * @param string $path
     * @return array
     * @throws ComposerJsonNotFoundException
     * @throws ComposerJsonInvalidException
     */
    public function fetchRemoteComposerJson(string $path): array
    {
        try {
            $response = $this->client->request('GET', $path);
        } catch (GuzzleException $e) {
            throw new ComposerJsonNotFoundException($e->getMessage(), $e->getCode());
        }
        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new ComposerJsonNotFoundException('Fetching composer.json did not return HTTP 200', 1557489013);
        }
        $json = json_decode($response->getBody()->getContents(), true);
        if (!is_array($json)) {
            throw new ComposerJsonInvalidException('Decoding composer.json did not return an object. Invalid json syntax?', 1558022442);
        }
        return $json;
    }

    /**
     * @param array $composerJson
     * @return ComposerJson
     */
    public function getComposerJsonObject(array $composerJson): ComposerJson
    {
        return new ComposerJson($composerJson);
    }

    /**
     * Create main deployment information from push event. This object will later be sanitized using
     * other methods of that service and dumped to disk for bamboo to fetch it again.
     *
     * @param PushEvent $pushEvent
     * @param ComposerJson $composerJson
     * @return DeploymentInformation
     * @throws \App\Exception\ComposerJsonInvalidException
     * @throws \App\Exception\DocsPackageDoNotCareBranch
     * @throws DocsComposerDependencyException
     */
    public function generateBuildInformation(PushEvent $pushEvent, ComposerJson $composerJson): DeploymentInformation
    {
        $this->assertComposerJsonContainsNecessaryData($composerJson);
        return new DeploymentInformation(
            $composerJson->getName(),
            $composerJson->getType(),
            $pushEvent->getRepositoryUrl(),
            $pushEvent->getUrlToComposerFile(),
            $pushEvent->getVersionString(),
            $composerJson->getMinimumTypoVersion(),
            $composerJson->getMaximumTypoVersion(),
            $this->privateDir,
            $this->subDir
        );
    }

    /**
     * Create main deployment information from DocumentationJar entity. This object will later be sanitized using
     * other methods of that service and dumped to disk for bamboo to fetch it again.
     *
     * @param DocumentationJar $documentationJar
     * @return DeploymentInformation
     * @throws \App\Exception\DocsPackageDoNotCareBranch
     */
    public function generateBuildInformationFromDocumentationJar(DocumentationJar $documentationJar): DeploymentInformation
    {
        return new DeploymentInformation(
            $documentationJar->getPackageName(),
            $documentationJar->getPackageType(),
            $documentationJar->getRepositoryUrl(),
            $documentationJar->getPublicComposerJsonUrl(),
            $documentationJar->getBranch(),
            $documentationJar->getMinimumTypoVersion(),
            $documentationJar->getMaximumTypoVersion(),
            $this->privateDir,
            $this->subDir
        );
    }

    /**
    /**
     * Verify the build request for a given vendor/package name and a given repository url is not
     * already registered with a different repository url.
     *
     * @param DeploymentInformation $deploymentInformation
     * @throws DocsPackageRegisteredWithDifferentRepositoryException
     */
    public function assertBuildWasTriggeredByRepositoryOwner(DeploymentInformation $deploymentInformation): void
    {
        $records = $this->documentationJarRepository->findBy([
            'packageName' => $deploymentInformation->packageName
        ]);
        foreach ($records as $record) {
            if ($record instanceof DocumentationJar && $record->getRepositoryUrl() !== $deploymentInformation->repositoryUrl) {
                throw new DocsPackageRegisteredWithDifferentRepositoryException(
                    'Package ' . $deploymentInformation->packageName . ' from repository . ' . $deploymentInformation->repositoryUrl
                    . ' is already registered for repository ' . $record->getRepositoryUrl(),
                    1553090750
                );
            }
        }
    }

    /**
     * Dump the deployment information file to disk to be fetched from bamboo later
     *
     * @param DeploymentInformation $deploymentInformation
     */
    public function dumpDeploymentInformationFile(DeploymentInformation $deploymentInformation): void
    {
        $absoluteDumpFile = $deploymentInformation->absoluteDumpFile;
        $fileContent = '#!/bin/bash' . PHP_EOL;
        foreach ($deploymentInformation->toArray() as $property => $value) {
            $fileContent .= $property . '=' . $value . PHP_EOL;
        }
        $this->fileSystem->dumpFile($absoluteDumpFile, $fileContent);
    }

    /**
     * Add / update a db entry for this docs deployment
     *
     * @param DeploymentInformation $deploymentInformation
     * @return DocumentationJar
     */
    public function registerDocumentationRendering(DeploymentInformation $deploymentInformation): DocumentationJar
    {
        $records = $this->documentationJarRepository->findBy([
            'repositoryUrl' => $deploymentInformation->repositoryUrl,
            'packageName' => $deploymentInformation->packageName,
            'targetBranchDirectory' => $deploymentInformation->targetBranchDirectory,
        ]);
        if (count($records) > 1) {
            throw new \RuntimeException(
                'Inconsistent database, there should be only one entry for'
                . ' repository ' . $deploymentInformation->repositoryUrl
                . ' package ' . $deploymentInformation->packageName
                . ' with target directory ' . $deploymentInformation->targetBranchDirectory
                . ' , but ' . count($records) . ' found.',
                1557755476
            );
        }
        $record = array_pop($records);
        if ($record instanceof DocumentationJar) {
            // Update source branch if needed. This way, that db entry always hold the latest tag the
            // documentation was rendered from, eg. if first target dir '5.7' was rendered from tag '5.7.1'
            // and later overriden by tag '5.7.2'
            $needsUpdate = false;
            if ($record->getBranch() !== $deploymentInformation->sourceBranch) {
                $record->setBranch($deploymentInformation->sourceBranch);
                $needsUpdate = true;
            }
            // Update typeLong and typeShort if these are empty (mostly migration purposes when this fields were added)
            if (empty($record->getTypeLong())) {
                $record->setTypeLong($deploymentInformation->typeLong);
                $needsUpdate = true;
            }
            if (empty($record->getTypeShort())) {
                $record->setTypeShort($deploymentInformation->typeShort);
                $needsUpdate = true;
            }
            if (empty($record->getPublicComposerJsonUrl())) {
                $record->setPublicComposerJsonUrl($deploymentInformation->publicComposerJsonUrl);
                $needsUpdate = true;
            }
            if (empty($record->getVendor())) {
                $record->setVendor($deploymentInformation->vendor);
                $needsUpdate = true;
            }
            if (empty($record->getName())) {
                $record->setName($deploymentInformation->name);
                $needsUpdate = true;
            }
            if (empty($record->getMinimumTypoVersion())) {
                $record->setMinimumTypoVersion($deploymentInformation->minimumTypoVersion);
                $needsUpdate = true;
            }
            if (empty($record->getMaximumTypoVersion())) {
                $record->setMaximumTypoVersion($deploymentInformation->maximumTypoVersion);
                $needsUpdate = true;
            }
            if ($needsUpdate) {
                $this->entityManager->flush();
            }
        } else {
            // No entry, yet - create one
            $documentationJar = (new DocumentationJar())
                ->setRepositoryUrl($deploymentInformation->repositoryUrl)
                ->setPublicComposerJsonUrl($deploymentInformation->publicComposerJsonUrl)
                ->setVendor($deploymentInformation->vendor)
                ->setName($deploymentInformation->name)
                ->setPackageName($deploymentInformation->packageName)
                ->setPackageType($deploymentInformation->packageType)
                ->setBranch($deploymentInformation->sourceBranch)
                ->setTargetBranchDirectory($deploymentInformation->targetBranchDirectory)
                ->setTypeLong($deploymentInformation->typeLong)
                ->setTypeShort($deploymentInformation->typeShort)
                ->setMinimumTypoVersion($deploymentInformation->minimumTypoVersion)
                ->setMaximumTypoVersion($deploymentInformation->maximumTypoVersion)
                ->setStatus(DocumentationStatus::STATUS_RENDERING)
                ->setBuildKey('');
            $this->entityManager->persist($documentationJar);
            $this->entityManager->flush();

            $record = $documentationJar;
        }

        return $record;
    }

    /**
     * @param DocumentationJar $documentationJar
     * @param string $buildKey
     */
    public function updateBuildKey(DocumentationJar $documentationJar, string $buildKey): void
    {
        $documentationJar->setBuildKey($buildKey);
        $this->entityManager->persist($documentationJar);
        $this->entityManager->flush();
    }

    /**
     * @param ComposerJson $composerJson
     * @throws DocsComposerDependencyException
     */
    private function assertComposerJsonContainsNecessaryData(ComposerJson $composerJson): void
    {
        if ($composerJson->getName() !== 'typo3/cms-core' && !$composerJson->requires('typo3/cms-core')) {
            throw new DocsComposerDependencyException('Dependency typo3/cms-core is missing', 1557310527);
        }
    }
}
