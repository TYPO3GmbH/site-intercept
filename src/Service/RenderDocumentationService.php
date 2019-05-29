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
use App\Extractor\DeploymentInformation;
use Psr\Log\LoggerInterface;

class RenderDocumentationService
{
    /**
     * @var DocumentationBuildInformationService
     */
    protected $documentationBuildInformationService;

    /**
     * @var BambooService
     */
    protected $bambooService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(DocumentationBuildInformationService $documentationBuildInformationService, BambooService $bambooService, LoggerInterface $logger)
    {
        $this->documentationBuildInformationService = $documentationBuildInformationService;
        $this->bambooService = $bambooService;
        $this->logger = $logger;
    }

    /**
     * @param DocumentationJar $documentationJar
     * @param string $scope
     * @return DeploymentInformation
     * @throws DocsPackageDoNotCareBranch
     */
    public function renderDocumentationByDocumentationJar(DocumentationJar $documentationJar, string $scope): DeploymentInformation
    {
        $buildInformation = $this->documentationBuildInformationService->generateBuildInformationFromDocumentationJar($documentationJar);
        $this->documentationBuildInformationService->dumpDeploymentInformationFile($buildInformation);
        $documentationJar = $this->documentationBuildInformationService->registerDocumentationRendering($buildInformation);
        $bambooBuildTriggered = $this->bambooService->triggerDocumentationPlan($buildInformation);
        if ($buildInformation->repositoryUrl === 'https://github.com/TYPO3-Documentation/DocsTypo3Org-Homepage.git'
            && ($buildInformation->sourceBranch === 'master' || $buildInformation->sourceBranch === 'new_docs_server')
        ) {
            // Additionally trigger the docs static web root plan, if we're dealing with the homepage repository
            /** @noinspection UnusedFunctionResultInspection */
            $this->bambooService->triggerDocmuntationServerWebrootResourcesPlan();
        }
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
}
