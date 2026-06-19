<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Dto\HistoryEntryDto;
use App\Entity\DocumentationJar;
use App\Enum\DocsRenderingHistoryStatus;
use App\Enum\DocumentationStatus;
use App\Enum\HistoryEntryType;
use App\Exception\DocsPackageDoNotCareBranch;
use App\Exception\DuplicateDocumentationRepositoryException;
use App\Extractor\DeploymentInformation;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use T3G\Bundle\Keycloak\Security\KeyCloakUser;

class RenderDocumentationService
{
    public function __construct(
        protected DocumentationBuildInformationService $documentationBuildInformationService,
        protected GithubService $githubService,
        protected HistoryService $historyService,
        protected LoggerInterface $logger,
        protected Security $security
    ) {
    }

    /**
     * @throws DocsPackageDoNotCareBranch
     * @throws DuplicateDocumentationRepositoryException
     */
    public function renderDocumentationByDocumentationJar(DocumentationJar $documentationJar, string $scope): DeploymentInformation
    {
        $buildInformation = $this->documentationBuildInformationService->generateBuildInformationFromDocumentationJar($documentationJar);
        $documentationJar = $this->documentationBuildInformationService->registerDocumentationRendering($buildInformation);
        $buildTriggered = $this->githubService->triggerDocumentationPlan($buildInformation);
        $this->documentationBuildInformationService->update($documentationJar, function (DocumentationJar $documentationJar) use ($buildTriggered) {
            $documentationJar->setStatus(DocumentationStatus::STATUS_RENDERING);
            $documentationJar->setReRenderNeeded(false);
            $documentationJar->setBuildKey($buildTriggered->buildResultKey);
        });
        $user = $this->security->getUser();
        $userIdentifier = 'Anon.';
        if ($user instanceof KeyCloakUser) {
            $userIdentifier = $user->getDisplayName();
        }
        $this->historyService->writeHistory(new HistoryEntryDto(
            type: HistoryEntryType::DOCS_RENDERING,
            status: DocsRenderingHistoryStatus::TRIGGERED,
            triggeredBy: $scope,
            groupEntry: $buildTriggered->buildResultKey,
            data: [
                'repository' => $buildInformation->repositoryUrl,
                'package' => $buildInformation->packageName,
                'sourceBranch' => $buildInformation->sourceBranch,
                'targetBranch' => $buildInformation->targetBranchDirectory,
                'bambooKey' => $buildTriggered->buildResultKey,
                'user' => $userIdentifier,
            ]
        ));

        return $buildInformation;
    }

    public function dumpRenderingInformationByDocumentationJar(DocumentationJar $documentationJar): DeploymentInformation
    {
        $buildInformation = $this->documentationBuildInformationService->generateBuildInformationFromDocumentationJar($documentationJar);
        $this->documentationBuildInformationService->dumpDeploymentInformationFile($buildInformation);

        return $buildInformation;
    }
}
