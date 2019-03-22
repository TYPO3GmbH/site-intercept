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

/**
 * This service class generates a `source`-able file that contains some deployment-related
 * environment variables based on the push event it receives.
 */
class DocumentationBuildInformationService
{
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
     * @var GeneralClient
     */
    private $client;

    /**
     * Constructor
     *
     * @param string $publicDir
     * @param string $cacheDir
     * @param EntityManagerInterface $entityManager
     * @param Filesystem $fileSystem
     * @param LoggerInterface $logger
     * @param GeneralClient $client
     */
    public function __construct(
        string $publicDir,
        string $cacheDir,
        EntityManagerInterface $entityManager,
        Filesystem $fileSystem,
        LoggerInterface $logger,
        GeneralClient $client
    ) {
        $this->publicDir = $publicDir;
        $this->cacheDir = $cacheDir;
        $this->entityManager = $entityManager;
        $this->fileSystem = $fileSystem;
        $this->logger = $logger;
        $this->documentationJarRepository = $this->entityManager->getRepository(DocumentationJar::class);
        $this->client = $client;
    }

    /**
     * @param GithubPushEventForDocs $pushEventForDocs
     * @return DocumentationBuildInformation
     * @throws \Exception
     */
    public function generateBuildInformation(GithubPushEventForDocs $pushEventForDocs): DocumentationBuildInformation
    {
        $buildTime = ceil(microtime(true) * 10000);
        $composerJson = json_decode($this->fetchRemoteFile($pushEventForDocs->composerFile));
        $deploymentInformation = new DeploymentInformation($composerJson, $pushEventForDocs->tagOrBranchName);
        if ($deploymentInformation->getTypeLong() === 'package') {
            $this->logger->info('Received unmapped package type "' . $composerJson['type'] . '" as defined in ' . $pushEventForDocs->composerFile . ', falling back to default');
        }

        $this->assertBuildWasTriggeredByRepositoryOwner($deploymentInformation, $pushEventForDocs->repositoryUrl);

        $privateFilePath = implode('/', [
            $this->cacheDir,
            'builds',
            $deploymentInformation->getVendor(),
            $deploymentInformation->getName(),
            $deploymentInformation->getBranch(),
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
     * @param DeploymentInformation $deploymentInformation
     * @param string $repositoryUrl
     */
    private function assertBuildWasTriggeredByRepositoryOwner(DeploymentInformation $deploymentInformation, string $repositoryUrl): void
    {
        $record = $this->documentationJarRepository->findOneBy([
            'packageName' => $deploymentInformation->getPackageName()
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
        $response = $this->client->request('GET', $path);
        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            throw new IOException('Could not read remote file ' . $path . ', received status code ' . $statusCode, 1553081065);
        }

        return $response->getBody()->getContents();
    }
}
