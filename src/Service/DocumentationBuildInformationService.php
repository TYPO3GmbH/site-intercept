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
use App\Exception\Composer\DependencyException;
use App\Extractor\ComposerJson;
use App\Exception\ComposerJsonNotFoundException;
use App\Exception\DocsPackageRegisteredWithDifferentRepositoryException;
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
     * @var MailService
     */
    private $mailService;

    /**
     * Constructor
     *
     * @param string $privateDir
     * @param string $subDir
     * @param EntityManagerInterface $entityManager
     * @param Filesystem $fileSystem
     * @param GeneralClient $client
     * @param MailService $mailService
     */
    public function __construct(
        string $privateDir,
        string $subDir,
        EntityManagerInterface $entityManager,
        Filesystem $fileSystem,
        GeneralClient $client,
        MailService $mailService
    ) {
        $this->privateDir = $privateDir;
        $this->subDir = $subDir;
        $this->entityManager = $entityManager;
        $this->fileSystem = $fileSystem;
        $this->documentationJarRepository = $this->entityManager->getRepository(DocumentationJar::class);
        $this->client = $client;
        $this->mailService = $mailService;
    }

    /**
     * Fetch composer.json from a remote repository to get more package information.
     *
     * @param string $path
     * @return string
     * @throws ComposerJsonNotFoundException
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
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Create main deployment information from push event. This object will later be sanitized using
     * other methods of that service and dumped to disk for bamboo to fetch it again.
     *
     * @param PushEvent $pushEvent
     * @param array $composerJson
     * @return DeploymentInformation
     * @throws \App\Exception\ComposerJsonInvalidException
     * @throws \App\Exception\DocsPackageDoNotCareBranch
     * @throws DependencyException
     */
    public function generateBuildInformation(PushEvent $pushEvent, array $composerJson): DeploymentInformation
    {
        $composerJsonObject = new ComposerJson($composerJson);
        try {
            $this->assertComposerJsonContainsNecessaryData($composerJsonObject);
        } catch (DependencyException $e) {
            $author = $composerJsonObject->getFirstAuthor();
            if (!empty($author['email']) && filter_var($author['email'], FILTER_VALIDATE_EMAIL)) {
                $this->sendRenderingFailedMail($pushEvent, $composerJsonObject, $e->getMessage());

                // Only re-throw exception if a notification mail can be sent
                throw $e;
            }
        }

        return new DeploymentInformation($composerJsonObject, $pushEvent, $this->privateDir, $this->subDir);
    }

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
     */
    public function registerDocumentationRendering(DeploymentInformation $deploymentInformation): void
    {
        // @todo: findBy() and verify there is only ONE record per url/packagename/targetBranch, otherwise we have inconsistent DB!
        $record = $this->documentationJarRepository->findOneBy([
            'repositoryUrl' => $deploymentInformation->repositoryUrl,
            'packageName' => $deploymentInformation->packageName,
            // @todo: Use target branch after it has been added to the model!
            'branch' => $deploymentInformation->targetBranchDirectory,
        ]);
        if ($record instanceof DocumentationJar) {
            // Update source branch if needed. This way, that db entry always hold the latest tag the
            // documentation was rendered from, eg. if first target dir '5.7' was rendered from tag '5.7.1'
            // and later overriden by tag '5.7.2'
            if ($record->getBranch() !== $deploymentInformation->sourceBranch) {
                $record->setBranch($deploymentInformation->sourceBranch);
                $this->entityManager->persist($record);
                $this->entityManager->flush();
            }
        } else {
            // No entry, yet - create one
            $documentationJar = (new DocumentationJar())
                ->setRepositoryUrl($deploymentInformation->repositoryUrl)
                ->setPackageName($deploymentInformation->packageName)
                ->setBranch($deploymentInformation->sourceBranch);
            // @todo: add target branch!
            $this->entityManager->persist($documentationJar);
            $this->entityManager->flush();
        }
    }

    /**
     * @param ComposerJson $composerJson
     * @throws DependencyException
     */
    private function assertComposerJsonContainsNecessaryData(ComposerJson $composerJson): void
    {
        if (!$composerJson->requires('typo3/cms-core')) {
            throw new DependencyException('Dependency typo3/cms-core is missing', 1557310527);
        }
    }

    /**
     * @param PushEvent $pushEvent
     * @param ComposerJson $composerJson
     * @param string $exceptionMessage
     * @return int
     */
    private function sendRenderingFailedMail(PushEvent $pushEvent, ComposerJson $composerJson, string $exceptionMessage): int
    {
        $message = $this->mailService->createMessageWithTemplate(
            'Documentation rendering failed',
            'emails/docs/renderingFailed.html.twig',
            [
                'author' => $composerJson->getFirstAuthor(),
                'package' => $composerJson->getName(),
                'pushEvent' => $pushEvent,
                'reasonPhrase' => $exceptionMessage,
            ]
        );
        $message
            ->setFrom('intercept@typo3.com')
            ->setTo($composerJson->getFirstAuthor()['email'])
        ;

        return $this->mailService->send($message);
    }
}
