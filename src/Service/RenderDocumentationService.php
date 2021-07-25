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
use App\Exception\DocsPackageDoNotCareBranch;
use App\Exception\DuplicateDocumentationRepositoryException;
use App\Extractor\DeploymentInformation;
use Psr\Log\LoggerInterface;

class RenderDocumentationService
{
    protected DocumentationBuildInformationService $documentationBuildInformationService;

    protected GithubService $githubService;

    protected LoggerInterface $logger;

    public function __construct(DocumentationBuildInformationService $documentationBuildInformationService, GithubService $githubService, LoggerInterface $logger)
    {
        $this->documentationBuildInformationService = $documentationBuildInformationService;
        $this->githubService = $githubService;
        $this->logger = $logger;
    }

    /**
     * @param DocumentationJar $documentationJar
     * @param string $scope
     * @return DeploymentInformation
     * @throws DocsPackageDoNotCareBranch
     * @throws DuplicateDocumentationRepositoryException
     */
    public function renderDocumentationByDocumentationJar(DocumentationJar $documentationJar, string $scope): DeploymentInformation
    {
        $buildInformation = $this->documentationBuildInformationService->generateBuildInformationFromDocumentationJar($documentationJar);
        $documentationJar = $this->documentationBuildInformationService->registerDocumentationRendering($buildInformation);
        $bambooBuildTriggered = $this->githubService->triggerDocumentationPlan($buildInformation);
        $this->documentationBuildInformationService->updateStatus($documentationJar, DocumentationStatus::STATUS_RENDERING);
        $this->documentationBuildInformationService->updateBuildKey($documentationJar, $bambooBuildTriggered->buildResultKey);
        $this->logger->info(
            'Triggered docs build',
            [
                'type' => 'docsRendering',
                'status' => 'triggered',
                'triggeredBy' => $scope,
                'repository' => $buildInformation->repositoryUrl,
                'package' => $buildInformation->packageName,
                'sourceBranch' => $buildInformation->sourceBranch,
                'targetBranch' => $buildInformation->targetBranchDirectory,
                'bambooKey' => $bambooBuildTriggered->buildResultKey,
            ]
        );
        return $buildInformation;
    }

    public function dumpRenderingInformationByDocumentationJar(DocumentationJar $documentationJar): DeploymentInformation
    {
        $buildInformation = $this->documentationBuildInformationService->generateBuildInformationFromDocumentationJar($documentationJar);
        $this->documentationBuildInformationService->dumpDeploymentInformationFile($buildInformation);
        return $buildInformation;
    }
}
