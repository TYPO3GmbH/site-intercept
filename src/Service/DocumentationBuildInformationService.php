<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\DocumentationJar;
use App\Enum\DocumentationStatus;
use App\Exception\Composer\DocsComposerDependencyException;
use App\Exception\Composer\DocsComposerMissingValueException;
use App\Exception\ComposerJsonInvalidException;
use App\Exception\ComposerJsonNotFoundException;
use App\Exception\DocsPackageDoNotCareBranch as DocsPackageDoNotCareBranchAlias;
use App\Exception\DocsPackageRegisteredWithDifferentRepositoryException;
use App\Exception\DuplicateDocumentationRepositoryException;
use App\Extractor\ComposerJson;
use App\Extractor\DeploymentInformation;
use App\Extractor\PushEvent;
use App\Repository\DocumentationJarRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * This service class generates a `source`-able file that contains some deployment-related
 * environment variables based on the push event it receives.
 */
readonly class DocumentationBuildInformationService
{
    public function __construct(
        private string $privateDir,
        private string $subDir,
        private DocumentationJarRepository $documentationJarRepository,
        private EntityManagerInterface $entityManager,
        private Filesystem $fileSystem,
        private ClientInterface $generalClient,
        private SlackService $slackService
    ) {
    }

    /**
     * Fetch composer.json from a remote repository to get more package information.
     *
     * @throws ComposerJsonNotFoundException
     * @throws ComposerJsonInvalidException
     */
    public function fetchRemoteComposerJson(string $path): array
    {
        try {
            $response = $this->generalClient->request('GET', $path);
        } catch (GuzzleException $e) {
            throw new ComposerJsonNotFoundException($e->getMessage(), $e->getCode());
        }
        $statusCode = $response->getStatusCode();
        if (200 !== $statusCode) {
            throw new ComposerJsonNotFoundException('Fetching composer.json did not return HTTP 200', 1557489013);
        }
        try {
            $json = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ComposerJsonInvalidException('Decoding ' . $path . ' did not return an object. Invalid json syntax?', 1558022442, $e);
        }

        return $json;
    }

    public function getComposerJsonObject(array $composerJson): ComposerJson
    {
        return new ComposerJson($composerJson);
    }

    /**
     * Create main deployment information from push event. This object will later be sanitized using
     * other methods of that service and dumped to disk for bamboo to fetch it again.
     *
     * @throws ComposerJsonInvalidException
     * @throws DocsPackageDoNotCareBranchAlias
     * @throws DocsComposerDependencyException
     * @throws DocsComposerMissingValueException
     */
    public function generateBuildInformation(PushEvent $pushEvent, ComposerJson $composerJson): DeploymentInformation
    {
        $this->assertComposerJsonContainsNecessaryData($composerJson);

        return new DeploymentInformation(
            $composerJson->getName(),
            $composerJson->getType(),
            $composerJson->getExtensionKey(),
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
     * @throws DocsPackageDoNotCareBranchAlias
     * @throws ComposerJsonInvalidException
     */
    public function generateBuildInformationFromDocumentationJar(DocumentationJar $documentationJar): DeploymentInformation
    {
        return new DeploymentInformation(
            $documentationJar->getPackageName(),
            $documentationJar->getPackageType(),
            $documentationJar->getExtensionKey() ?? '',
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
     * /**
     * Verify the build request for a given vendor/package name and a given repository url is not
     * already registered with a different repository url.
     *
     * @throws DocsPackageRegisteredWithDifferentRepositoryException
     */
    public function assertBuildWasTriggeredByRepositoryOwner(DeploymentInformation $deploymentInformation): void
    {
        $records = $this->documentationJarRepository->findBy([
            'packageName' => $deploymentInformation->packageName,
        ]);
        foreach ($records as $record) {
            if ($record->getRepositoryUrl() !== $deploymentInformation->repositoryUrl) {
                throw new DocsPackageRegisteredWithDifferentRepositoryException($deploymentInformation->packageName, 'Package ' . $deploymentInformation->packageName . ' from repository ' . $deploymentInformation->repositoryUrl . ' is already registered for repository ' . $record->getRepositoryUrl(), 1553090750);
            }
        }
    }

    /**
     * Dump the deployment information file to disk to be fetched from bamboo later.
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
     * Add / update a db entry for this docs deployment.
     *
     * @throws DuplicateDocumentationRepositoryException
     */
    public function registerDocumentationRendering(DeploymentInformation $deploymentInformation): DocumentationJar
    {
        $records = $this->documentationJarRepository->findBy([
            'repositoryUrl' => $deploymentInformation->repositoryUrl,
            'packageName' => $deploymentInformation->packageName,
            'targetBranchDirectory' => $deploymentInformation->targetBranchDirectory,
        ]);
        if (count($records) > 1) {
            throw new DuplicateDocumentationRepositoryException('Inconsistent database, there should be only one entry for repository ' . $deploymentInformation->repositoryUrl . ' package ' . $deploymentInformation->packageName . ' with target directory ' . $deploymentInformation->targetBranchDirectory . ' , but ' . count($records) . ' found.', 1557755476);
        }
        $record = array_pop($records);
        if ($record instanceof DocumentationJar) {
            // Update source branch if needed. This way, that db entry always hold the latest tag the
            // documentation was rendered from, e.g. if first target dir '5.7' was rendered from tag '5.7.1'
            // and later overridden by tag '5.7.2'
            if ($record->getBranch() !== $deploymentInformation->sourceBranch) {
                $record->setBranch($deploymentInformation->sourceBranch);
            }
            // Update typeLong and typeShort if these are empty (mostly migration purposes when this fields were added)
            if (empty($record->getTypeLong())) {
                $record->setTypeLong($deploymentInformation->typeLong);
            }
            if (empty($record->getTypeShort())) {
                $record->setTypeShort($deploymentInformation->typeShort);
            }
            if (empty($record->getExtensionKey()) || $record->getExtensionKey() !== $deploymentInformation->extensionKey) {
                $record->setExtensionKey($deploymentInformation->extensionKey);
            }
            if (empty($record->getPublicComposerJsonUrl())) {
                $record->setPublicComposerJsonUrl($deploymentInformation->publicComposerJsonUrl);
            }
            if (empty($record->getVendor())) {
                $record->setVendor($deploymentInformation->vendor);
            }
            if (empty($record->getName())) {
                $record->setName($deploymentInformation->name);
            }
            if (empty($record->getMinimumTypoVersion()) || $record->getMinimumTypoVersion() !== $deploymentInformation->minimumTypoVersion) {
                $record->setMinimumTypoVersion($deploymentInformation->minimumTypoVersion);
            }
            if (empty($record->getMaximumTypoVersion()) || $record->getMaximumTypoVersion() !== $deploymentInformation->maximumTypoVersion) {
                $record->setMaximumTypoVersion($deploymentInformation->maximumTypoVersion);
            }
            // status is not updated at this point, this is done later by controllers
            $this->entityManager->flush();
        } else {
            // No entry, yet - create one
            $documentationJar = (new DocumentationJar())
                ->setRepositoryUrl($deploymentInformation->repositoryUrl)
                ->setPublicComposerJsonUrl($deploymentInformation->publicComposerJsonUrl)
                ->setVendor($deploymentInformation->vendor)
                ->setName($deploymentInformation->name)
                ->setPackageName($deploymentInformation->packageName)
                ->setPackageType($deploymentInformation->packageType)
                ->setExtensionKey($deploymentInformation->extensionKey)
                ->setBranch($deploymentInformation->sourceBranch)
                ->setTargetBranchDirectory($deploymentInformation->targetBranchDirectory)
                ->setTypeLong($deploymentInformation->typeLong)
                ->setTypeShort($deploymentInformation->typeShort)
                ->setMinimumTypoVersion($deploymentInformation->minimumTypoVersion)
                ->setMaximumTypoVersion($deploymentInformation->maximumTypoVersion)
                ->setReRenderNeeded(false)
                // Set a new record to 'rendered' for now, this will be updated by controllers later on
                ->setStatus(DocumentationStatus::STATUS_RENDERED)
                ->setBuildKey('');
            // Check if this repository is entirely new (aka, no branches at all known)
            // And mark it as new if needed
            $branchExists = $this->documentationJarRepository->findOneBy([
                'repositoryUrl' => $deploymentInformation->repositoryUrl,
                'packageName' => $deploymentInformation->packageName,
            ]);
            if (null === $branchExists) {
                $documentationJar->setNew(true);
                $documentationJar->setApproved(false);
                $this->slackService->sendRepositoryDiscoveryMessage($documentationJar);
            } else {
                $documentationJar->setNew(false);
                $documentationJar->setApproved($branchExists->isApproved());
            }

            if (!$documentationJar->isApproved()) {
                $documentationJar->setStatus(DocumentationStatus::STATUS_AWAITING_APPROVAL);
            }

            $this->entityManager->persist($documentationJar);
            $this->entityManager->flush();

            $record = $documentationJar;
        }

        return $record;
    }

    public function update(DocumentationJar $documentationJar, callable $callback): void
    {
        $callback($documentationJar);
        $this->entityManager->flush();
    }

    public function updateReRenderNeeded(DocumentationJar $documentationJar, bool $reRenderNeeded): void
    {
        $documentationJar->setReRenderNeeded($reRenderNeeded);
        $this->entityManager->flush();
    }

    /**
     * @throws DocsComposerDependencyException
     */
    public function assertComposerJsonContainsNecessaryData(ComposerJson $composerJson): void
    {
        if (null === $composerJson->getCoreRequirement() && (str_contains($composerJson->getType(), 'typo3-cms') && 'typo3/cms-core' !== $composerJson->getName())) {
            throw new DocsComposerDependencyException('Dependency typo3/cms-core is missing', 1557310527);
        }
    }
}
