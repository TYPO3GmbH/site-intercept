<?php
declare(strict_types = 1);

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
use App\Exception\DocsPackageDoNotCareBranch;
use App\Exception\DocsPackageRegisteredWithDifferentRepositoryException;
use App\Extractor\BambooBuildTriggered;
use App\Extractor\DeploymentInformation;
use App\Repository\DocumentationJarRepository;
use App\Utility\RepositoryUrlUtility;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DocumentationService
{
    protected RenderDocumentationService $renderDocumentationService;

    protected DocumentationJarRepository $docsRepository;

    protected EntityManagerInterface $entityManager;

    protected DocumentationBuildInformationService $documentationBuildInformationService;

    protected GithubService $githubService;

    protected LoggerInterface $logger;

    protected SlackService $slackService;

    public function __construct(
        RenderDocumentationService $renderDocumentationService,
        DocumentationJarRepository $documentationJarRepository,
        EntityManagerInterface $entityManager,
        DocumentationBuildInformationService $documentationBuildInformationService,
        GithubService $githubService,
        LoggerInterface $logger,
        SlackService $slackService
    ) {
        $this->renderDocumentationService = $renderDocumentationService;
        $this->docsRepository = $documentationJarRepository;
        $this->entityManager = $entityManager;
        $this->documentationBuildInformationService = $documentationBuildInformationService;
        $this->githubService = $githubService;
        $this->logger = $logger;
        $this->slackService = $slackService;
    }

    /**
     * @param string $repositoryUrl
     * @param string $packageName
     * @throws DocsPackageRegisteredWithDifferentRepositoryException
     */
    public function assertUrlIsUnique(string $repositoryUrl, string $packageName): void
    {
        $existWithDifferentUrl = $this->docsRepository
            ->createQueryBuilder('d')
            ->where('d.repositoryUrl <> :repositoryUrl')
            ->andWhere('d.packageName = :packageName')
            ->setParameter('repositoryUrl', $repositoryUrl)
            ->setParameter('packageName', $packageName)
            ->getQuery()
            ->getResult();
        if (!empty($existWithDifferentUrl)) {
            throw new DocsPackageRegisteredWithDifferentRepositoryException('This package already exists with a different repository url', 1558697388);
        }
    }

    /**
     * @param DocumentationJar $doc
     * @param string $branchName
     * @throws ComposerJsonInvalidException
     * @throws ComposerJsonNotFoundException
     * @throws DocsComposerDependencyException
     * @throws DocsComposerMissingValueException
     */
    public function enrichWithComposerInformation(DocumentationJar $doc, string $branchName): void
    {
        if (empty($doc->getPublicComposerJsonUrl())) {
            $publicComposerJsonUrl = RepositoryUrlUtility::resolveComposerJsonUrl($doc->getRepositoryUrl(), $branchName);
            $doc->setPublicComposerJsonUrl($publicComposerJsonUrl);
        }
        $composerJson = $this->documentationBuildInformationService->fetchRemoteComposerJson($doc->getPublicComposerJsonUrl());
        $composerJsonObject = $this->documentationBuildInformationService->getComposerJsonObject($composerJson);
        $this->documentationBuildInformationService->assertComposerJsonContainsNecessaryData($composerJsonObject);
        $doc
            ->setPackageName($composerJsonObject->getName())
            ->setPackageType($composerJsonObject->getType())
            ->setExtensionKey($composerJsonObject->getExtensionKey())
            ->setMinimumTypoVersion($composerJsonObject->getMinimumTypoVersion())
            ->setMaximumTypoVersion($composerJsonObject->getMaximumTypoVersion());
    }

    /**
     * @param DocumentationJar $doc
     * @param DeploymentInformation $deploymentInformation
     */
    public function enrichWithDeploymentInformation(DocumentationJar $doc, DeploymentInformation $deploymentInformation): void
    {
        $doc
            ->setStatus(DocumentationStatus::STATUS_RENDERING)
            ->setBuildKey('')
            ->setVendor($deploymentInformation->vendor)
            ->setName($deploymentInformation->name)
            ->setPackageName($deploymentInformation->packageName)
            ->setExtensionKey($deploymentInformation->extensionKey)
            ->setTargetBranchDirectory($deploymentInformation->targetBranchDirectory)
            ->setTypeLong($deploymentInformation->typeLong)
            ->setTypeShort($deploymentInformation->typeShort)
            ->setMinimumTypoVersion($deploymentInformation->minimumTypoVersion)
            ->setMaximumTypoVersion($deploymentInformation->maximumTypoVersion)
            ->setBranch($deploymentInformation->sourceBranch);
    }

    /**
     * @param array $branches
     * @param DocumentationJar $documentationJar
     * @throws DocsPackageDoNotCareBranch
     */
    protected function handleBranches(array $branches, DocumentationJar $documentationJar): void
    {
        foreach ($branches as $branchName => $short) {
            // Check if in the mean time someone already rendered this branch
            $alreadyExists = $this->docsRepository->findBy([
                'repositoryUrl' => $documentationJar->getRepositoryUrl(),
                'targetBranchDirectory' => $short,
            ]);

            if (count($alreadyExists) > 0) {
                continue;
            }

            $doc = (new DocumentationJar())
                ->setRepositoryUrl($documentationJar->getRepositoryUrl())
                ->setBranch($branchName);
            try {
                $this->enrichWithComposerInformation($doc, $branchName);
                $deploymentInformation = $this->documentationBuildInformationService
                    ->generateBuildInformationFromDocumentationJar($doc);
            } catch (
                ComposerJsonNotFoundException |
                ComposerJsonInvalidException |
                DocsComposerDependencyException |
                DocsPackageDoNotCareBranch |
                DocsComposerMissingValueException |
                \UnexpectedValueException |
                DocsPackageRegisteredWithDifferentRepositoryException $e
            ) {
                $this->logger->warning('Cannot render documentation: ' . $e->getMessage(), [
                    'type' => 'docsRendering',
                    'status' => 'commandFailed',
                    'triggeredBy' => 'CLI',
                    'exceptionCode' => $e->getCode(),
                    'exceptionMessage' => $e->getMessage(),
                    'repository' => $documentationJar->getRepositoryUrl(),
                    'branch' => $branchName,
                ]);
                continue;
            }

            if ($deploymentInformation !== null) {
                $this->enrichWithDeploymentInformation($doc, $deploymentInformation);

                $doc
                    ->setReRenderNeeded(false)
                    ->setNew(false)
                    ->setApproved(true);

                $bambooBuildTriggered = $this->triggerBuild($doc);
                $doc->setBuildKey($bambooBuildTriggered->buildResultKey);
                $this->entityManager->persist($doc);

                // Flushing in ForEach is needed in case Bamboo finishes the build before this command
                // has finished running through all the branches
                $this->entityManager->flush();
            }
        }
    }

    /**
     * Adds documentationJar to database and triggers build
     *
     * @param DocumentationJar $doc
     * @param DeploymentInformation $deploymentInformation
     * @throws DocsPackageDoNotCareBranch
     */
    public function addNewDocumentationBuild(DocumentationJar $doc, DeploymentInformation $deploymentInformation): void
    {
        $this->enrichWithDeploymentInformation($doc, $deploymentInformation);
        $doc->setReRenderNeeded(false);

        // Check if this repository is entirely new (aka, no branches at all known)
        // And mark it as new if needed
        $existingDocs = $this->docsRepository->findBy([
            'repositoryUrl' => $deploymentInformation->repositoryUrl,
            'packageName' => $deploymentInformation->packageName,
        ]);

        if (count($existingDocs) === 0) {
            $doc->setNew(true);
            $this->slackService->sendRepositoryDiscoveryMessage($doc);
        } else {
            $doc->setNew(false);
        }

        $approved = false;
        foreach ($existingDocs as $existingDoc) {
            if ($existingDoc->isApproved()) {
                $bambooBuildTriggered = $this->triggerBuild($doc);
                $doc->setBuildKey($bambooBuildTriggered->buildResultKey);
                $doc->setApproved(true);
                $approved = true;
                break;
            }
        }
        if (!$approved) {
            $doc
                ->setApproved(false)
                ->setStatus(DocumentationStatus::STATUS_AWAITING_APPROVAL);
        }

        $this->entityManager->persist($doc);
        $this->entityManager->flush();
    }

    /**
     * @param DocumentationJar $documentationJar
     * @throws DocsPackageDoNotCareBranch
     */
    public function handleNewRepository(DocumentationJar $documentationJar): void
    {
        // No need to filter these anymore, this is already done in the service
        $branches = (new GitRepositoryService())->getBranchesFromRepositoryUrl($documentationJar->getRepositoryUrl());

        $this->handleBranches($branches, $documentationJar);

        $documentationJar->setNew(false);
        $this->entityManager->persist($documentationJar);
        $this->entityManager->flush();
    }

    /**
     * @param DocumentationJar $doc
     * @return BambooBuildTriggered
     * @throws DocsPackageDoNotCareBranch
     */
    public function triggerBuild(DocumentationJar $doc): BambooBuildTriggered
    {
        $deploymentInformation = $this->documentationBuildInformationService->generateBuildInformationFromDocumentationJar($doc);
        return $this->githubService->triggerDocumentationPlan($deploymentInformation);
    }
}
