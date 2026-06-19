<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Dto\HistoryEntryDto;
use App\Entity\DocumentationJar;
use App\Enum\DocsRenderingHistoryStatus;
use App\Enum\DocumentationStatus;
use App\Enum\HistoryEntryTrigger;
use App\Enum\HistoryEntryType;
use App\Exception\Composer\DocsComposerDependencyException;
use App\Exception\Composer\DocsComposerMissingValueException;
use App\Exception\ComposerJsonInvalidException;
use App\Exception\ComposerJsonNotFoundException;
use App\Exception\DocsNoRstChangesException;
use App\Exception\DocsPackageDoNotCareBranch;
use App\Exception\DocsPackageRegisteredWithDifferentRepositoryException;
use App\Exception\GitBranchDeletedException;
use App\Exception\GithubHookPingException;
use App\Exception\UnsupportedWebHookRequestException;
use App\Repository\RepositoryBlacklistEntryRepository;
use App\Service\DocumentationBuildInformationService;
use App\Service\GithubService;
use App\Service\HistoryService;
use App\Service\MailService;
use App\Service\WebHookService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use T3G\Bundle\Keycloak\Security\KeyCloakUser;

/**
 * Trigger documentation rendering from a repository hook that calls
 * https://docs-hook.typo3.org/ or /docs/ route.
 */
class DocsRenderingController extends AbstractController
{
    #[Route(path: '/docs', name: 'docs_to_bamboo')]
    #[Route(path: '/', name: 'docs_hook_to_bamboo', host: 'docs-hook.typo3.org')]
    public function index(
        Request $request,
        GithubService $githubService,
        WebHookService $webhookService,
        HistoryService $historyService,
        DocumentationBuildInformationService $documentationBuildInformationService,
        RepositoryBlacklistEntryRepository $repositoryBlacklistEntryRepository,
        LoggerInterface $logger,
        MailService $mailService
    ): Response {
        $user = $this->getUser();
        $userIdentifier = 'Anon.';
        if ($user instanceof KeyCloakUser) {
            $userIdentifier = $user->getDisplayName();
        }
        try {
            $pushEvents = $webhookService->createPushEvent($request);
            $erroredPushes = 0;
            $errorMessage = '';

            foreach ($pushEvents as $pushEvent) {
                $composerJson = $documentationBuildInformationService->fetchRemoteComposerJson($pushEvent->getUrlToComposerFile());
                $composerAsObject = $documentationBuildInformationService->getComposerJsonObject($composerJson);

                try {
                    if ($repositoryBlacklistEntryRepository->isBlacklisted($pushEvent->getRepositoryUrl())) {
                        $historyService->writeHistory(new HistoryEntryDto(
                            type: HistoryEntryType::DOCS_RENDERING,
                            status: DocsRenderingHistoryStatus::BLACKLISTED,
                            triggeredBy: HistoryEntryTrigger::API,
                            data: [
                                'repository' => $pushEvent->getRepositoryUrl(),
                                'composerFile' => $pushEvent->getUrlToComposerFile(),
                                'payload' => $request->getContent(),
                                'user' => $userIdentifier,
                            ]
                        ));
                        continue;
                    }
                    $buildInformation = $documentationBuildInformationService->generateBuildInformation($pushEvent, $composerAsObject);
                    $documentationBuildInformationService->assertBuildWasTriggeredByRepositoryOwner($buildInformation);
                    $documentationJar = $documentationBuildInformationService->registerDocumentationRendering($buildInformation);
                    // Trigger build only if status is not already "I'm rendering". Else, only set a flag that re-rendering is needed.
                    // The re-render flag is used and reset by the post build controller if it is set, to trigger a new
                    // rendering. This suppresses multiple builds for one repo at the same time and prevents conditions where
                    // an older build finishes after a younger triggered build which would overwrite the result af the later build.
                    if (DocumentationStatus::STATUS_RENDERING === $documentationJar->getStatus()) {
                        $documentationBuildInformationService->updateReRenderNeeded($documentationJar, true);
                        $historyService->writeHistory(new HistoryEntryDto(
                            type: HistoryEntryType::DOCS_RENDERING,
                            status: DocsRenderingHistoryStatus::RE_RENDER_NEEDED,
                            triggeredBy: HistoryEntryTrigger::API,
                            data: [
                                'repository' => $buildInformation->repositoryUrl,
                                'package' => $buildInformation->packageName,
                                'sourceBranch' => $buildInformation->sourceBranch,
                                'targetBranch' => $buildInformation->targetBranchDirectory,
                                'user' => $userIdentifier,
                            ]
                        ));
                    } elseif (!$documentationJar->isApproved()) {
                        $logger->info('Repository present, but not approved. Do nothing.', [$documentationJar]);
                    } else {
                        $buildTriggered = $githubService->triggerDocumentationPlan($buildInformation);
                        $documentationBuildInformationService->update($documentationJar, function (DocumentationJar $documentationJar) use ($buildTriggered) {
                            $documentationJar->setStatus(DocumentationStatus::STATUS_RENDERING);
                            $documentationJar->setReRenderNeeded(false);
                            $documentationJar->setBuildKey($buildTriggered->buildResultKey);
                        });
                        $historyService->writeHistory(new HistoryEntryDto(
                            type: HistoryEntryType::DOCS_RENDERING,
                            status: DocsRenderingHistoryStatus::TRIGGERED,
                            triggeredBy: HistoryEntryTrigger::API,
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
                    $historyService->writeHistory(new HistoryEntryDto(
                        type: HistoryEntryType::DOCS_RENDERING,
                        status: DocsRenderingHistoryStatus::NO_COMPOSER_JSON,
                        triggeredBy: HistoryEntryTrigger::API,
                        data: [
                            'exceptionCode' => $e->getCode(),
                            'exceptionMessage' => $e->getMessage(),
                            'repository' => $pushEvent->getRepositoryUrl(),
                            'composerFile' => $pushEvent->getUrlToComposerFile(),
                            'payload' => $request->getContent(),
                            'user' => $userIdentifier,
                        ]
                    ));
                    ++$erroredPushes;
                    $errorMessage = 'No composer.json found, invalid or unable to fetch. See https://intercept.typo3.com for more information.';
                    continue;
                } catch (ComposerJsonInvalidException $e) {
                    $historyService->writeHistory(new HistoryEntryDto(
                        type: HistoryEntryType::DOCS_RENDERING,
                        status: DocsRenderingHistoryStatus::INVALID_COMPOSER_JSON,
                        triggeredBy: HistoryEntryTrigger::API,
                        data: [
                            'exceptionCode' => $e->getCode(),
                            'exceptionMessage' => $e->getMessage(),
                            'repository' => $pushEvent->getRepositoryUrl(),
                            'composerFile' => $pushEvent->getUrlToComposerFile(),
                            'payload' => $request->getContent(),
                            'user' => $userIdentifier,
                        ]
                    ));
                    ++$erroredPushes;
                    $errorMessage = 'Invalid composer.json. See https://intercept.typo3.com for more information.';
                    continue;
                } catch (DocsPackageRegisteredWithDifferentRepositoryException $e) {
                    $historyService->writeHistory(new HistoryEntryDto(
                        type: HistoryEntryType::DOCS_RENDERING,
                        status: DocsRenderingHistoryStatus::PACKAGE_REGISTERED_WITH_DIFFERENT_REPOSITORY,
                        triggeredBy: HistoryEntryTrigger::API,
                        data: [
                            'exceptionCode' => $e->getCode(),
                            'exceptionMessage' => $e->getMessage(),
                            'repository' => $pushEvent->getRepositoryUrl(),
                            'package' => $e->getPackageName(),
                            'user' => $userIdentifier,
                        ]
                    ));
                    ++$erroredPushes;
                    $errorMessage = 'Package already registered for different repository. See https://intercept.typo3.com for more information.';
                    continue;
                } catch (DocsPackageDoNotCareBranch $e) {
                    $historyService->writeHistory(new HistoryEntryDto(
                        type: HistoryEntryType::DOCS_RENDERING,
                        status: DocsRenderingHistoryStatus::NO_RELEVANT_BRANCH_OR_TAG,
                        triggeredBy: HistoryEntryTrigger::API,
                        data: [
                            'exceptionCode' => $e->getCode(),
                            'exceptionMessage' => $e->getMessage(),
                            'repository' => $pushEvent->getRepositoryUrl(),
                            'sourceBranch' => $pushEvent->getVersionString(),
                            'user' => $userIdentifier,
                        ]
                    ));
                    ++$erroredPushes;
                    $errorMessage = 'Branch or tag name ignored for documentation rendering. See https://intercept.typo3.com for more information.';
                    continue;
                } catch (DocsComposerMissingValueException $e) {
                    $historyService->writeHistory(new HistoryEntryDto(
                        type: HistoryEntryType::DOCS_RENDERING,
                        status: DocsRenderingHistoryStatus::MISSING_VALUE_IN_COMPOSER_JSON,
                        triggeredBy: HistoryEntryTrigger::API,
                        data: [
                            'exceptionCode' => $e->getCode(),
                            'exceptionMessage' => $e->getMessage(),
                            'repository' => $pushEvent->getRepositoryUrl(),
                            'sourceBranch' => $pushEvent->getVersionString(),
                            'user' => $userIdentifier,
                        ]
                    ));
                    ++$erroredPushes;
                    $errorMessage = 'A mandatory value is missing in the composer.json. See https://intercept.typo3.com for more information.';
                    continue;
                } catch (DocsComposerDependencyException $e) {
                    $historyService->writeHistory(new HistoryEntryDto(
                        type: HistoryEntryType::DOCS_RENDERING,
                        status: DocsRenderingHistoryStatus::CORE_DEPENDENCY_NOT_SET,
                        triggeredBy: HistoryEntryTrigger::API,
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
                            $mailService->sendMailToAuthorDueToMissingDependency($pushEvent, $composerAsObject, $e->getMessage());
                        }
                    } catch (DocsComposerMissingValueException) {
                        // Do not send mail if 'authors' is not set in composer.json
                    }
                    ++$erroredPushes;
                    $errorMessage = 'Dependencies are not fulfilled. See https://intercept.typo3.com for more information.';
                    continue;
                }
            }
            if (count($pushEvents) === $erroredPushes) {
                return new Response($errorMessage, Response::HTTP_PRECONDITION_FAILED);
            }

            return new Response(null, Response::HTTP_NO_CONTENT);
        } catch (GithubHookPingException $e) {
            // Hook payload is a 'GitHub ping' - log that as 'info / success' with the
            // url that hook came from. This is triggered by GitHub when a new web hook is added,
            // we want to be nice and make this one succeed.
            $historyService->writeHistory(new HistoryEntryDto(
                type: HistoryEntryType::DOCS_RENDERING,
                status: DocsRenderingHistoryStatus::GITHUB_PING,
                triggeredBy: HistoryEntryTrigger::API,
                data: [
                    'repository' => $e->getRepositoryUrl(),
                ]
            ));

            return new Response('Received github ping. Please push content to the repository to render some documentation. See https://intercept.typo3.com for more information.');
        } catch (UnsupportedWebHookRequestException $e) {
            // Hook payload could not be identified as hook that should trigger rendering
            $historyService->writeHistory(new HistoryEntryDto(
                type: HistoryEntryType::DOCS_RENDERING,
                status: DocsRenderingHistoryStatus::UNSUPPORTED_HOOK,
                triggeredBy: HistoryEntryTrigger::API,
                data: [
                    'headers' => $request->headers,
                    'payload' => $request->getContent(),
                    'exceptionCode' => $e->getCode(),
                    'user' => $userIdentifier,
                ]
            ));

            return new Response('Invalid hook payload. See https://intercept.typo3.com for more information.', Response::HTTP_PRECONDITION_FAILED);
        } catch (GitBranchDeletedException $e) {
            $historyService->writeHistory(new HistoryEntryDto(
                type: HistoryEntryType::DOCS_RENDERING,
                status: DocsRenderingHistoryStatus::BRANCH_DELETED,
                triggeredBy: HistoryEntryTrigger::API,
                data: [
                    'exceptionCode' => $e->getCode(),
                    'exceptionMessage' => $e->getMessage(),
                    'user' => $userIdentifier,
                ]
            ));

            return new Response('The branch in this push event has been deleted.', Response::HTTP_PRECONDITION_FAILED);
        } catch (DocsNoRstChangesException $e) {
            $historyService->writeHistory(new HistoryEntryDto(
                type: HistoryEntryType::DOCS_RENDERING,
                status: DocsRenderingHistoryStatus::BRANCH_NO_RST_CHANGES,
                triggeredBy: HistoryEntryTrigger::API,
                data: [
                    'exceptionCode' => $e->getCode(),
                    'exceptionMessage' => $e->getMessage(),
                    'user' => $userIdentifier,
                ]
            ));

            return new Response(null, Response::HTTP_NO_CONTENT);
        }
    }
}
