<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Enum\DocumentationStatus;
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
use App\Service\MailService;
use App\Service\WebHookService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Trigger documentation rendering from a repository hook that calls
 * https://docs-hook.typo3.org/ or /docs/ route
 */
class DocsRenderingController extends AbstractController
{
    /**
     * @Route("/docs", name="docs_to_bamboo")
     * @Route("/", host="docs-hook.typo3.org", name="docs_hook_to_bamboo")
     * @param Request $request
     * @param GithubService $githubService
     * @param WebHookService $webhookService
     * @param DocumentationBuildInformationService $documentationBuildInformationService
     * @param RepositoryBlacklistEntryRepository $repositoryBlacklistEntryRepository
     * @param LoggerInterface $logger
     * @param MailService $mailService
     * @return Response
     */
    public function index(
        Request $request,
        GithubService $githubService,
        WebHookService $webhookService,
        DocumentationBuildInformationService $documentationBuildInformationService,
        RepositoryBlacklistEntryRepository $repositoryBlacklistEntryRepository,
        LoggerInterface $logger,
        MailService $mailService
    ): Response {
        try {
            $pushEvents = $webhookService->createPushEvent($request);
            $erroredPushes = 0;
            $errorMessage = '';

            foreach ($pushEvents as $pushEvent) {
                try {
                    if ($repositoryBlacklistEntryRepository->isBlacklisted($pushEvent->getRepositoryUrl())) {
                        $logger->warning(
                            'Cannot render documentation: The repository at ' . $pushEvent->getRepositoryUrl() . ' due to it being blacklisted.',
                            [
                                'type' => 'docsRendering',
                                'status' => 'blacklisted',
                                'triggeredBy' => 'api',
                                'repository' => $pushEvent->getRepositoryUrl(),
                                'composerFile' => $pushEvent->getUrlToComposerFile(),
                                'payload' => $request->getContent(),
                            ]
                        );
                        continue;
                    }
                    $composerJson = $documentationBuildInformationService->fetchRemoteComposerJson($pushEvent->getUrlToComposerFile());
                    $composerAsObject = $documentationBuildInformationService->getComposerJsonObject($composerJson);
                    $buildInformation = $documentationBuildInformationService->generateBuildInformation($pushEvent, $composerAsObject);
                    $documentationBuildInformationService->assertBuildWasTriggeredByRepositoryOwner($buildInformation);
                    $documentationJar = $documentationBuildInformationService->registerDocumentationRendering($buildInformation);
                    // Trigger build only if status is not already "I'm rendering". Else, only set a flag that re-rendering is needed.
                    // The re-render flag is used and reset by the post build controller if it is set, to trigger a new
                    // rendering. This suppresses multiple builds for one repo at the same time and prevents conditions where
                    // an older build finishes after a younger triggered build which would overwrite the result af the later build.
                    if ($documentationJar->getStatus() === DocumentationStatus::STATUS_RENDERING) {
                        $documentationBuildInformationService->updateReRenderNeeded($documentationJar, true);
                        $logger->info(
                            'Registered docs build for re-rendering',
                            [
                                'type' => 'docsRendering',
                                'status' => 're-render-needed',
                                'triggeredBy' => 'api',
                                'repository' => $buildInformation->repositoryUrl,
                                'package' => $buildInformation->packageName,
                                'sourceBranch' => $buildInformation->sourceBranch,
                                'targetBranch' => $buildInformation->targetBranchDirectory,
                            ]
                        );
                    } elseif (!$documentationJar->isApproved()) {
                        $logger->info('Repository present, but not approved. Do nothing.', [$documentationJar]);
                    } else {
                        $buildTriggered = $githubService->triggerDocumentationPlan($buildInformation);
                        $documentationBuildInformationService->updateStatus($documentationJar, DocumentationStatus::STATUS_RENDERING);
                        $documentationBuildInformationService->updateBuildKey($documentationJar, $buildTriggered->buildResultKey);
                        $logger->info(
                            'Triggered docs build',
                            [
                                'type' => 'docsRendering',
                                'status' => 'triggered',
                                'triggeredBy' => 'api',
                                'repository' => $buildInformation->repositoryUrl,
                                'package' => $buildInformation->packageName,
                                'sourceBranch' => $buildInformation->sourceBranch,
                                'targetBranch' => $buildInformation->targetBranchDirectory,
                                'bambooKey' => $buildTriggered->buildResultKey,
                            ]
                        );
                    }
                } catch (ComposerJsonNotFoundException $e) {
                    // Repository did not provide a composer.json, or fetch failed
                    $logger->warning(
                        'Cannot render documentation: The repository at ' . $pushEvent->getRepositoryUrl() . ' MUST have a composer.json file on top level.',
                        [
                            'type' => 'docsRendering',
                            'status' => 'noComposerJson',
                            'triggeredBy' => 'api',
                            'exceptionCode' => $e->getCode(),
                            'exceptionMessage' => $e->getMessage(),
                            'repository' => $pushEvent->getRepositoryUrl(),
                            'composerFile' => $pushEvent->getUrlToComposerFile(),
                            'payload' => $request->getContent(),
                        ]
                    );
                    $erroredPushes++;
                    $errorMessage = 'No composer.json found, invalid or unable to fetch. See https://intercept.typo3.com for more information.';
                    continue;
                } catch (ComposerJsonInvalidException $e) {
                    $logger->warning(
                        'Cannot render documentation: ' . $e->getMessage(),
                        [
                            'type' => 'docsRendering',
                            'status' => 'invalidComposerJson',
                            'triggeredBy' => 'api',
                            'exceptionCode' => $e->getCode(),
                            'exceptionMessage' => $e->getMessage(),
                            'repository' => $pushEvent->getRepositoryUrl(),
                            'composerFile' => $pushEvent->getUrlToComposerFile(),
                            'payload' => $request->getContent(),
                        ]
                    );
                    $erroredPushes++;
                    $errorMessage = 'Invalid composer.json. See https://intercept.typo3.com for more information.';
                    continue;
                } catch (DocsPackageRegisteredWithDifferentRepositoryException $e) {
                    $logger->warning(
                        'Can not render documentation: ' . $e->getMessage(),
                        [
                            'type' => 'docsRendering',
                            'status' => 'packageRegisteredWithDifferentRepository',
                            'triggeredBy' => 'api',
                            'exceptionCode' => $e->getCode(),
                            'exceptionMessage' => $e->getMessage(),
                            'repository' => $pushEvent->getRepositoryUrl(),
                            'package' => $buildInformation->packageName,
                        ]
                    );
                    $erroredPushes++;
                    $errorMessage = 'Package already registered for different repository. See https://intercept.typo3.com for more information.';
                    continue;
                } catch (DocsPackageDoNotCareBranch $e) {
                    $logger->warning(
                        'Cannot render documentation: ' . $e->getMessage(),
                        [
                            'type' => 'docsRendering',
                            'status' => 'noRelevantBranchOrTag',
                            'triggeredBy' => 'api',
                            'exceptionCode' => $e->getCode(),
                            'exceptionMessage' => $e->getMessage(),
                            'repository' => $pushEvent->getRepositoryUrl(),
                            'sourceBranch' => $pushEvent->getVersionString(),
                        ]
                    );
                    $erroredPushes++;
                    $errorMessage = 'Branch or tag name ignored for documentation rendering. See https://intercept.typo3.com for more information.';
                    continue;
                } catch (DocsComposerMissingValueException $e) {
                    $logger->warning(
                        'Cannot render documentation: ' . $e->getMessage(),
                        [
                            'type' => 'docsRendering',
                            'status' => 'missingValueInComposerJson',
                            'triggeredBy' => 'api',
                            'exceptionCode' => $e->getCode(),
                            'exceptionMessage' => $e->getMessage(),
                            'repository' => $pushEvent->getRepositoryUrl(),
                            'sourceBranch' => $pushEvent->getVersionString(),
                        ]
                    );
                    $erroredPushes++;
                    $errorMessage = 'A mandatory value is missing in the composer.json. See https://intercept.typo3.com for more information.';
                    continue;
                } catch (DocsComposerDependencyException $e) {
                    $logger->warning(
                        'Cannot render documentation: ' . $e->getMessage(),
                        [
                            'type' => 'docsRendering',
                            'status' => 'coreDependencyNotSet',
                            'triggeredBy' => 'api',
                            'exceptionCode' => $e->getCode(),
                            'exceptionMessage' => $e->getMessage(),
                            'repository' => $pushEvent->getRepositoryUrl(),
                            'sourceBranch' => $pushEvent->getVersionString(),
                        ]
                    );
                    try {
                        $author = $composerAsObject->getFirstAuthor();
                        if (filter_var($author['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                            $mailService->sendMailToAuthorDueToMissingDependency($pushEvent, $composerAsObject, $e->getMessage());
                        }
                    } catch (DocsComposerMissingValueException $e) {
                        // Do not send mail if 'authors' is not set in composer.json
                    }
                    $erroredPushes++;
                    $errorMessage = 'Dependencies are not fulfilled. See https://intercept.typo3.com for more information.';
                    continue;
                }
            }
            if (count($pushEvents) === $erroredPushes) {
                return new Response($errorMessage, Response::HTTP_PRECONDITION_FAILED);
            }

            return new Response(null, Response::HTTP_NO_CONTENT);
        } catch (GithubHookPingException $e) {
            // Hook payload is a 'github ping' - log that as "info / success' with the
            // url that hook came from. This is triggered by github when a new web hook is added,
            // we want to be nice and make this one succeed.
            $logger->info(
                'Docs hook ping from github repository ' . $e->getRepositoryUrl(),
                [
                    'type' => 'docsRendering',
                    'status' => 'githubPing',
                    'triggeredBy' => 'api',
                    'repository' => $e->getRepositoryUrl(),
                ]
            );
            return new Response('Received github ping. Please push content to the repository to render some documentation. See https://intercept.typo3.com for more information.');
        } catch (UnsupportedWebHookRequestException $e) {
            // Hook payload could not be identified as hook that should trigger rendering
            $logger->warning(
                'Can not render documentation: ' . $e->getMessage(),
                [
                    'type' => 'docsRendering',
                    'status' => 'unsupportedHook',
                    'headers' => $request->headers,
                    'payload' => $request->getContent(),
                    'triggeredBy' => 'api',
                    'exceptionCode' => $e->getCode(),
                ]
            );
            return new Response('Invalid hook payload. See https://intercept.typo3.com for more information.', Response::HTTP_PRECONDITION_FAILED);
        } catch (GitBranchDeletedException $e) {
            $logger->warning(
                'Cannot render documentation: ' . $e->getMessage(),
                [
                    'type' => 'docsRendering',
                    'status' => 'branchDeleted',
                    'triggeredBy' => 'api',
                    'exceptionCode' => $e->getCode(),
                    'exceptionMessage' => $e->getMessage(),
                ]
            );
            return new Response('The branch in this push event has been deleted.', Response::HTTP_PRECONDITION_FAILED);
        } catch (DocsNoRstChangesException $e) {
            $logger->warning(
                'Cannot render documentation: ' . $e->getMessage(),
                [
                    'type' => 'docsRendering',
                    'status' => 'branchNoRstChanges',
                    'triggeredBy' => 'api',
                    'exceptionCode' => $e->getCode(),
                    'exceptionMessage' => $e->getMessage(),
                ]
            );
            return new Response(null, RESPONSE::HTTP_NO_CONTENT);
        }
    }
}
