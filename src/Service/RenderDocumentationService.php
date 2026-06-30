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
use App\Enum\DocumentationRenderingTrigger;
use App\Enum\DocumentationStatus;
use App\Enum\HistoryEntryTrigger;
use App\Enum\HistoryEntryType;
use App\Exception\Composer\DocsComposerDependencyException;
use App\Exception\Composer\DocsComposerMissingValueException;
use App\Exception\ComposerJsonInvalidException;
use App\Exception\ComposerJsonNotFoundException;
use App\Exception\DisallowedComposerJsonUrlException;
use App\Exception\DocsPackageDoNotCareBranch;
use App\Exception\DocsPackageRegisteredWithDifferentRepositoryException;
use App\Exception\DocumentationRenderingRequestDeclinedException;
use App\Exception\DuplicateDocumentationRepositoryException;
use App\Exception\UnknownComposerJsonUrlException;
use App\Extractor\DeploymentInformation;
use App\Extractor\PushEvent;
use App\Repository\RepositoryBlacklistEntryRepository;
use App\Utility\RepositoryUrlUtility;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use T3G\Bundle\Keycloak\Security\KeyCloakUser;

final readonly class RenderDocumentationService
{
    public function __construct(
        private DocumentationBuildInformationService $documentationBuildInformationService,
        private GithubService $githubService,
        private HistoryService $historyService,
        private LoggerInterface $logger,
        private DocumentationQuarantineService $documentationQuarantineService,
        private RepositoryBlacklistEntryRepository $repositoryBlacklistEntryRepository,
        private MailService $mailService,
        private Security $security
    ) {
    }

    public function requestDocumentationRendering(PushEvent $pushEvent, DocumentationRenderingTrigger $trigger): void
    {
        $userIdentifier = $this->security->getUser() instanceof KeyCloakUser ? $this->security->getUser()->getDisplayName() : 'Anon.';

        try {
            $this->documentationBuildInformationService->updateLastHit(RepositoryUrlUtility::getNormalizedDomain($pushEvent->getRepositoryUrl()));

            $composerJson = $this->documentationBuildInformationService->fetchRemoteComposerJson($pushEvent->getUrlToComposerFile());
        } catch (UnknownComposerJsonUrlException $e) {
            if (!$this->documentationQuarantineService->isQuarantined($pushEvent)) {
                $documentationQuarantine = $this->documentationQuarantineService->quarantine($pushEvent);
                $this->documentationBuildInformationService->notifyAboutUnknownRepositoryDomain($documentationQuarantine);

                $this->historyService->writeHistory(new HistoryEntryDto(
                    type: HistoryEntryType::DOCS_RENDERING,
                    status: DocsRenderingHistoryStatus::UNKNOWN_REPOSITORY_DOMAIN,
                    triggeredBy: $trigger->toHistoryEntryTrigger(),
                    data: [
                        'repository' => $pushEvent->getRepositoryUrl(),
                        'composerFile' => $pushEvent->getUrlToComposerFile(),
                        'payload' => $pushEvent->getPayload(),
                        'user' => $userIdentifier,
                    ]
                ));
            }

            throw new DocumentationRenderingRequestDeclinedException(sprintf('composer.json\'s host domain %s is unknown to the system and needs approval', $e->normalizedHost), 1782293322, $e);
        } catch (DisallowedComposerJsonUrlException $e) {
            $this->historyService->writeHistory(new HistoryEntryDto(
                type: HistoryEntryType::DOCS_RENDERING,
                status: DocsRenderingHistoryStatus::UNKNOWN_REPOSITORY_DOMAIN,
                triggeredBy: $trigger->toHistoryEntryTrigger(),
                data: [
                    'repository' => $pushEvent->getRepositoryUrl(),
                    'composerFile' => $pushEvent->getUrlToComposerFile(),
                    'payload' => $pushEvent->getPayload(),
                    'user' => $userIdentifier,
                ]
            ));

            throw new DocumentationRenderingRequestDeclinedException(sprintf('composer.json\'s host domain %s is disallowed for rendering request', $e->normalizedHost), 1782294348, $e);
        }

        $composerAsObject = $this->documentationBuildInformationService->getComposerJsonObject($composerJson);
        try {
            if ($this->repositoryBlacklistEntryRepository->isBlacklisted($pushEvent->getRepositoryUrl())) {
                $this->historyService->writeHistory(new HistoryEntryDto(
                    type: HistoryEntryType::DOCS_RENDERING,
                    status: DocsRenderingHistoryStatus::BLACKLISTED,
                    triggeredBy: $trigger->toHistoryEntryTrigger(),
                    data: [
                        'repository' => $pushEvent->getRepositoryUrl(),
                        'composerFile' => $pushEvent->getUrlToComposerFile(),
                        'payload' => $pushEvent->getPayload(),
                        'user' => $userIdentifier,
                    ]
                ));

                return;
            }
            $buildInformation = $this->documentationBuildInformationService->generateBuildInformation($pushEvent, $composerAsObject);
            $this->documentationBuildInformationService->assertBuildWasTriggeredByRepositoryOwner($buildInformation);
            $documentationJar = $this->documentationBuildInformationService->registerDocumentationRendering($buildInformation);
            // Trigger build only if status is not already "I'm rendering". Else, only set a flag that re-rendering is needed.
            // The re-render flag is used and reset by the post build controller if it is set, to trigger a new
            // rendering. This suppresses multiple builds for one repo at the same time and prevents conditions where
            // an older build finishes after a younger triggered build which would overwrite the result af the later build.
            if (DocumentationStatus::STATUS_RENDERING === $documentationJar->getStatus()) {
                $this->documentationBuildInformationService->updateReRenderNeeded($documentationJar, true);
                $this->historyService->writeHistory(new HistoryEntryDto(
                    type: HistoryEntryType::DOCS_RENDERING,
                    status: DocsRenderingHistoryStatus::RE_RENDER_NEEDED,
                    triggeredBy: $trigger->toHistoryEntryTrigger(),
                    data: [
                        'repository' => $buildInformation->repositoryUrl,
                        'package' => $buildInformation->packageName,
                        'sourceBranch' => $buildInformation->sourceBranch,
                        'targetBranch' => $buildInformation->targetBranchDirectory,
                        'user' => $userIdentifier,
                    ]
                ));
            } elseif (!$documentationJar->isApproved()) {
                $this->logger->info('Repository present, but not approved. Do nothing.', [$documentationJar]);
            } else {
                $buildTriggered = $this->githubService->triggerDocumentationPlan($buildInformation);
                $this->documentationBuildInformationService->update($documentationJar, function (DocumentationJar $documentationJar) use ($buildTriggered) {
                    $documentationJar->setStatus(DocumentationStatus::STATUS_RENDERING);
                    $documentationJar->setReRenderNeeded(false);
                    $documentationJar->setBuildKey($buildTriggered->buildResultKey);
                });
                $this->historyService->writeHistory(new HistoryEntryDto(
                    type: HistoryEntryType::DOCS_RENDERING,
                    status: DocsRenderingHistoryStatus::TRIGGERED,
                    triggeredBy: $trigger->toHistoryEntryTrigger(),
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
            }
        } catch (ComposerJsonNotFoundException $e) {
            // Repository did not provide a composer.json, or fetch failed
            $this->historyService->writeHistory(new HistoryEntryDto(
                type: HistoryEntryType::DOCS_RENDERING,
                status: DocsRenderingHistoryStatus::NO_COMPOSER_JSON,
                triggeredBy: $trigger->toHistoryEntryTrigger(),
                data: [
                    'exceptionCode' => $e->getCode(),
                    'exceptionMessage' => $e->getMessage(),
                    'repository' => $pushEvent->getRepositoryUrl(),
                    'composerFile' => $pushEvent->getUrlToComposerFile(),
                    'payload' => $pushEvent->getPayload(),
                    'user' => $userIdentifier,
                ]
            ));

            throw new DocumentationRenderingRequestDeclinedException('No composer.json found, invalid or unable to fetch. See https://intercept.typo3.com for more information.', 1782294357, $e);
        } catch (ComposerJsonInvalidException $e) {
            $this->historyService->writeHistory(new HistoryEntryDto(
                type: HistoryEntryType::DOCS_RENDERING,
                status: DocsRenderingHistoryStatus::INVALID_COMPOSER_JSON,
                triggeredBy: $trigger->toHistoryEntryTrigger(),
                data: [
                    'exceptionCode' => $e->getCode(),
                    'exceptionMessage' => $e->getMessage(),
                    'repository' => $pushEvent->getRepositoryUrl(),
                    'composerFile' => $pushEvent->getUrlToComposerFile(),
                    'payload' => $pushEvent->getPayload(),
                    'user' => $userIdentifier,
                ]
            ));

            throw new DocumentationRenderingRequestDeclinedException('Invalid composer.json. See https://intercept.typo3.com for more information.', 1782294366, $e);
        } catch (DocsPackageRegisteredWithDifferentRepositoryException $e) {
            $this->historyService->writeHistory(new HistoryEntryDto(
                type: HistoryEntryType::DOCS_RENDERING,
                status: DocsRenderingHistoryStatus::PACKAGE_REGISTERED_WITH_DIFFERENT_REPOSITORY,
                triggeredBy: $trigger->toHistoryEntryTrigger(),
                data: [
                    'exceptionCode' => $e->getCode(),
                    'exceptionMessage' => $e->getMessage(),
                    'repository' => $pushEvent->getRepositoryUrl(),
                    'package' => $e->getPackageName(),
                    'user' => $userIdentifier,
                ]
            ));

            throw new DocumentationRenderingRequestDeclinedException('Package already registered for different repository. See https://intercept.typo3.com for more information.', 1782294378, $e);
        } catch (DocsPackageDoNotCareBranch $e) {
            $this->historyService->writeHistory(new HistoryEntryDto(
                type: HistoryEntryType::DOCS_RENDERING,
                status: DocsRenderingHistoryStatus::NO_RELEVANT_BRANCH_OR_TAG,
                triggeredBy: $trigger->toHistoryEntryTrigger(),
                data: [
                    'exceptionCode' => $e->getCode(),
                    'exceptionMessage' => $e->getMessage(),
                    'repository' => $pushEvent->getRepositoryUrl(),
                    'sourceBranch' => $pushEvent->getVersionString(),
                    'user' => $userIdentifier,
                ]
            ));

            throw new DocumentationRenderingRequestDeclinedException('Branch or tag name ignored for documentation rendering. See https://intercept.typo3.com for more information.', 1782294385, $e);
        } catch (DocsComposerMissingValueException $e) {
            $this->historyService->writeHistory(new HistoryEntryDto(
                type: HistoryEntryType::DOCS_RENDERING,
                status: DocsRenderingHistoryStatus::MISSING_VALUE_IN_COMPOSER_JSON,
                triggeredBy: $trigger->toHistoryEntryTrigger(),
                data: [
                    'exceptionCode' => $e->getCode(),
                    'exceptionMessage' => $e->getMessage(),
                    'repository' => $pushEvent->getRepositoryUrl(),
                    'sourceBranch' => $pushEvent->getVersionString(),
                    'user' => $userIdentifier,
                ]
            ));

            throw new DocumentationRenderingRequestDeclinedException('A mandatory value is missing in the composer.json. See https://intercept.typo3.com for more information.', 1782294401, $e);
        } catch (DocsComposerDependencyException $e) {
            $this->historyService->writeHistory(new HistoryEntryDto(
                type: HistoryEntryType::DOCS_RENDERING,
                status: DocsRenderingHistoryStatus::CORE_DEPENDENCY_NOT_SET,
                triggeredBy: $trigger->toHistoryEntryTrigger(),
                data: [
                    'exceptionCode' => $e->getCode(),
                    'exceptionMessage' => $e->getMessage(),
                    'repository' => $pushEvent->getRepositoryUrl(),
                    'sourceBranch' => $pushEvent->getVersionString(),
                    'user' => $userIdentifier,
                ]
            ));
            try {
                $author = $composerAsObject->getFirstAuthor();
                if (filter_var($author['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                    $this->mailService->sendMailToAuthorDueToMissingDependency($pushEvent, $composerAsObject, $e->getMessage());
                }
            } catch (DocsComposerMissingValueException) {
                // Do not send mail if 'authors' is not set in composer.json
            }

            throw new DocumentationRenderingRequestDeclinedException('Dependencies are not fulfilled. See https://intercept.typo3.com for more information.', 1782294424, $e);
        }
    }

    /**
     * @throws DocsPackageDoNotCareBranch
     * @throws DuplicateDocumentationRepositoryException
     */
    public function renderDocumentationByDocumentationJar(DocumentationJar $documentationJar, HistoryEntryTrigger $scope): DeploymentInformation
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
