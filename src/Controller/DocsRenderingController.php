<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\HistoryEntry;
use App\Enum\DocsRenderingHistoryStatus;
use App\Enum\DocumentationStatus;
use App\Enum\HistoryEntryTrigger;
use App\Enum\HistoryEntryType;
use App\Exception\Composer\DocsComposerDependencyException;
use App\Exception\Composer\DocsComposerMissingValueException;
use App\Exception\ComposerJsonInvalidException;
use App\Exception\ComposerJsonNotFoundException;
use App\Exception\DocsNoRstChangesException;
use App\Exception\DocsNotValidException;
use App\Exception\DocsPackageDoNotCareBranch;
use App\Exception\DocsPackageRegisteredWithDifferentRepositoryException;
use App\Exception\GitBranchDeletedException;
use App\Exception\GithubHookPingException;
use App\Exception\UnsupportedWebHookRequestException;
use App\Repository\RepositoryBlacklistEntryRepository;
use App\Service\DocumentationBuildInformationService;
use App\Service\DocumentationValidationService;
use App\Service\GithubService;
use App\Service\MailService;
use App\Service\WebHookService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use T3G\Bundle\Keycloak\Security\KeyCloakUser;

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
     * @param DocumentationValidationService $documentationValidationService
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
        DocumentationValidationService $documentationValidationService,
        RepositoryBlacklistEntryRepository $repositoryBlacklistEntryRepository,
        LoggerInterface $logger,
        MailService $mailService
    ): Response {
        $manager = $this->getDoctrine()->getManager();
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
                try {
                    if ($repositoryBlacklistEntryRepository->isBlacklisted($pushEvent->getRepositoryUrl())) {
                        $manager->persist(
                            (new HistoryEntry())
                                ->setType(HistoryEntryType::DOCS_RENDERING)
                                ->setStatus(DocsRenderingHistoryStatus::BLACKLISTED)
                                ->setData(
                                    [
                                        'type' => HistoryEntryType::DOCS_RENDERING,
                                        'status' => DocsRenderingHistoryStatus::BLACKLISTED,
                                        'triggeredBy' => HistoryEntryTrigger::API,
                                        'repository' => $pushEvent->getRepositoryUrl(),
                                        'composerFile' => $pushEvent->getUrlToComposerFile(),
                                        'payload' => $request->getContent(),
                                        'user' => $userIdentifier
                                    ]
                                )
                        );
                        $manager->flush();
                        continue;
                    }
                    $composerJson = $documentationBuildInformationService->fetchRemoteComposerJson($pushEvent->getUrlToComposerFile());
                    $composerAsObject = $documentationBuildInformationService->getComposerJsonObject($composerJson);
                    $buildInformation = $documentationBuildInformationService->generateBuildInformation($pushEvent, $composerAsObject);
                    $documentationBuildInformationService->assertBuildWasTriggeredByRepositoryOwner($buildInformation);
                    $documentationValidationService->validate($pushEvent, $composerAsObject);
                    $documentationJar = $documentationBuildInformationService->registerDocumentationRendering($buildInformation);
                    // Trigger build only if status is not already "I'm rendering". Else, only set a flag that re-rendering is needed.
                    // The re-render flag is used and reset by the post build controller if it is set, to trigger a new
                    // rendering. This suppresses multiple builds for one repo at the same time and prevents conditions where
                    // an older build finishes after a younger triggered build which would overwrite the result af the later build.
                    if ($documentationJar->getStatus() === DocumentationStatus::STATUS_RENDERING) {
                        $documentationBuildInformationService->updateReRenderNeeded($documentationJar, true);
                        $manager->persist(
                            (new HistoryEntry())
                                ->setType(HistoryEntryType::DOCS_RENDERING)
                                ->setStatus(DocsRenderingHistoryStatus::RE_RENDER_NEEDED)
                                ->setData(
                                    [
                                        'type' => HistoryEntryType::DOCS_RENDERING,
                                        'status' => DocsRenderingHistoryStatus::RE_RENDER_NEEDED,
                                        'triggeredBy' => HistoryEntryTrigger::API,
                                        'repository' => $buildInformation->repositoryUrl,
                                        'package' => $buildInformation->packageName,
                                        'sourceBranch' => $buildInformation->sourceBranch,
                                        'targetBranch' => $buildInformation->targetBranchDirectory,
                                        'user' => $userIdentifier
                                    ]
                                )
                        );
                        $manager->flush();
                    } elseif (!$documentationJar->isApproved()) {
                        $logger->info('Repository present, but not approved. Do nothing.', [$documentationJar]);
                    } else {
                        $buildTriggered = $githubService->triggerDocumentationPlan($buildInformation);
                        $documentationBuildInformationService->updateStatus($documentationJar, DocumentationStatus::STATUS_RENDERING);
                        $documentationBuildInformationService->updateBuildKey($documentationJar, $buildTriggered->buildResultKey);
                        $manager->persist(
                            (new HistoryEntry())
                                ->setType(HistoryEntryType::DOCS_RENDERING)
                                ->setStatus(HistoryEntryTrigger::API)
                                ->setGroupEntry($buildTriggered->buildResultKey)
                                ->setData(
                                    [
                                        'type' => HistoryEntryType::DOCS_RENDERING,
                                        'status' => DocsRenderingHistoryStatus::TRIGGERED,
                                        'triggeredBy' => HistoryEntryTrigger::API,
                                        'repository' => $buildInformation->repositoryUrl,
                                        'package' => $buildInformation->packageName,
                                        'sourceBranch' => $buildInformation->sourceBranch,
                                        'targetBranch' => $buildInformation->targetBranchDirectory,
                                        'bambooKey' => $buildTriggered->buildResultKey,
                                        'user' => $userIdentifier
                                    ]
                                )
                        );
                        $manager->flush();
                    }
                } catch (ComposerJsonNotFoundException $e) {
                    // Repository did not provide a composer.json, or fetch failed
                    $manager->persist(
                        (new HistoryEntry())
                        ->setType(HistoryEntryType::DOCS_RENDERING)
                        ->setStatus(DocsRenderingHistoryStatus::NO_COMPOSER_JSON)
                        ->setData(
                            [
                                'type' => HistoryEntryType::DOCS_RENDERING,
                                'status' => DocsRenderingHistoryStatus::NO_COMPOSER_JSON,
                                'triggeredBy' => HistoryEntryTrigger::API,
                                'exceptionCode' => $e->getCode(),
                                'exceptionMessage' => $e->getMessage(),
                                'repository' => $pushEvent->getRepositoryUrl(),
                                'composerFile' => $pushEvent->getUrlToComposerFile(),
                                'payload' => $request->getContent(),
                                'user' => $userIdentifier
                            ]
                        )
                    );
                    $manager->flush();
                    $erroredPushes++;
                    $errorMessage = 'No composer.json found, invalid or unable to fetch. See https://intercept.typo3.com for more information.';
                    continue;
                } catch (ComposerJsonInvalidException $e) {
                    $manager->persist(
                        (new HistoryEntry())
                            ->setType(HistoryEntryType::DOCS_RENDERING)
                            ->setStatus(DocsRenderingHistoryStatus::INVALID_COMPOSER_JSON)
                            ->setData(
                                [
                                    'type' => HistoryEntryType::DOCS_RENDERING,
                                    'status' => DocsRenderingHistoryStatus::INVALID_COMPOSER_JSON,
                                    'triggeredBy' => HistoryEntryTrigger::API,
                                    'exceptionCode' => $e->getCode(),
                                    'exceptionMessage' => $e->getMessage(),
                                    'repository' => $pushEvent->getRepositoryUrl(),
                                    'composerFile' => $pushEvent->getUrlToComposerFile(),
                                    'payload' => $request->getContent(),
                                    'user' => $userIdentifier
                                ]
                            )
                    );
                    $manager->flush();
                    $erroredPushes++;
                    $errorMessage = 'Invalid composer.json. See https://intercept.typo3.com for more information.';
                    continue;
                } catch (DocsPackageRegisteredWithDifferentRepositoryException $e) {
                    $manager->persist(
                        (new HistoryEntry())
                        ->setType(HistoryEntryType::DOCS_RENDERING)
                        ->setStatus(DocsRenderingHistoryStatus::PACKAGE_REGISTERED_WITH_DIFFERENT_REPOSITORY)
                        ->setData(
                            [
                                'type' => HistoryEntryType::DOCS_RENDERING,
                                'status' => DocsRenderingHistoryStatus::PACKAGE_REGISTERED_WITH_DIFFERENT_REPOSITORY,
                                'triggeredBy' => HistoryEntryTrigger::API,
                                'exceptionCode' => $e->getCode(),
                                'exceptionMessage' => $e->getMessage(),
                                'repository' => $pushEvent->getRepositoryUrl(),
                                'package' => $buildInformation->packageName,
                                'user' => $userIdentifier
                            ]
                        )
                    );
                    $manager->flush();
                    $erroredPushes++;
                    $errorMessage = 'Package already registered for different repository. See https://intercept.typo3.com for more information.';
                    continue;
                } catch (DocsPackageDoNotCareBranch $e) {
                    $manager->persist(
                        (new HistoryEntry())
                            ->setType(HistoryEntryType::DOCS_RENDERING)
                            ->setStatus(DocsRenderingHistoryStatus::NO_RELEVANT_BRANCH_OR_TAG)
                            ->setData(
                                [
                                    'type' => HistoryEntryType::DOCS_RENDERING,
                                    'status' => DocsRenderingHistoryStatus::NO_RELEVANT_BRANCH_OR_TAG,
                                    'triggeredBy' => HistoryEntryTrigger::API,
                                    'exceptionCode' => $e->getCode(),
                                    'exceptionMessage' => $e->getMessage(),
                                    'repository' => $pushEvent->getRepositoryUrl(),
                                    'sourceBranch' => $pushEvent->getVersionString(),
                                    'user' => $userIdentifier
                                ]
                            )
                    );
                    $manager->flush();
                    $erroredPushes++;
                    $errorMessage = 'Branch or tag name ignored for documentation rendering. See https://intercept.typo3.com for more information.';
                    continue;
                } catch (DocsComposerMissingValueException $e) {
                    $manager->persist(
                        (new HistoryEntry())
                            ->setType(HistoryEntryType::DOCS_RENDERING)
                            ->setStatus(DocsRenderingHistoryStatus::MISSING_VALUE_IN_COMPOSER_JSON)
                            ->setData(
                                [
                                    'type' => HistoryEntryType::DOCS_RENDERING,
                                    'status' => DocsRenderingHistoryStatus::MISSING_VALUE_IN_COMPOSER_JSON,
                                    'triggeredBy' => HistoryEntryTrigger::API,
                                    'exceptionCode' => $e->getCode(),
                                    'exceptionMessage' => $e->getMessage(),
                                    'repository' => $pushEvent->getRepositoryUrl(),
                                    'sourceBranch' => $pushEvent->getVersionString(),
                                    'user' => $userIdentifier
                                ]
                            )
                    );
                    $manager->flush();
                    $erroredPushes++;
                    $errorMessage = 'A mandatory value is missing in the composer.json. See https://intercept.typo3.com for more information.';
                    continue;
                } catch (DocsComposerDependencyException $e) {
                    $manager->persist(
                        (new HistoryEntry())
                            ->setType(HistoryEntryType::DOCS_RENDERING)
                            ->setStatus(DocsRenderingHistoryStatus::CORE_DEPENDENCY_NOT_SET)
                            ->setData(
                                [
                                    'type' => HistoryEntryType::DOCS_RENDERING,
                                    'status' => DocsRenderingHistoryStatus::CORE_DEPENDENCY_NOT_SET,
                                    'triggeredBy' => HistoryEntryTrigger::API,
                                    'exceptionCode' => $e->getCode(),
                                    'exceptionMessage' => $e->getMessage(),
                                    'repository' => $pushEvent->getRepositoryUrl(),
                                    'sourceBranch' => $pushEvent->getVersionString(),
                                    'user' => $userIdentifier
                                ]
                            )
                    );
                    $manager->flush();
                    try {
                        $author = $composerAsObject->getFirstAuthor();
                        if (filter_var($author['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                            $mailService->sendMailToAuthorDueToFailedRendering($pushEvent, $composerAsObject, $e->getMessage());
                        }
                    } catch (DocsComposerMissingValueException $e) {
                        // Do not send mail if 'authors' is not set in composer.json
                    }
                    $erroredPushes++;
                    $errorMessage = 'Dependencies are not fulfilled. See https://intercept.typo3.com for more information.';
                    continue;
                } catch (DocsNotValidException $e) {
                    $manager->persist(
                        (new HistoryEntry())
                            ->setType(HistoryEntryType::DOCS_RENDERING)
                            ->setStatus(DocsRenderingHistoryStatus::INVALID_DOCS)
                            ->setData(
                                [
                                    'type' => HistoryEntryType::DOCS_RENDERING,
                                    'status' => DocsRenderingHistoryStatus::INVALID_DOCS,
                                    'triggeredBy' => HistoryEntryTrigger::API,
                                    'exceptionCode' => $e->getCode(),
                                    'exceptionMessage' => $e->getMessage(),
                                    'repository' => $pushEvent->getRepositoryUrl(),
                                    'sourceBranch' => $pushEvent->getVersionString(),
                                    'user' => $userIdentifier
                                ]
                            )
                    );
                    $manager->flush();
                    try {
                        $author = $composerAsObject->getFirstAuthor();
                        if (filter_var($author['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                            $mailService->sendMailToAuthorDueToFailedRendering($pushEvent, $composerAsObject, $e->getMessage());
                        }
                    } catch (DocsComposerMissingValueException $e) {
                        // Do not send mail if 'authors' is not set in composer.json
                    }
                    $erroredPushes++;
                    $errorMessage = 'Documentation format is invalid. See https://intercept.typo3.com for more information.';
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
            $manager->persist(
                (new HistoryEntry())
                    ->setType(HistoryEntryType::DOCS_RENDERING)
                    ->setStatus(DocsRenderingHistoryStatus::GITHUB_PING)
                    ->setData(
                        [
                            'type' => HistoryEntryType::DOCS_RENDERING,
                            'status' => DocsRenderingHistoryStatus::GITHUB_PING,
                            'triggeredBy' => HistoryEntryTrigger::API,
                            'repository' => $e->getRepositoryUrl(),
                        ]
                    )
            );
            $manager->flush();
            return new Response('Received github ping. Please push content to the repository to render some documentation. See https://intercept.typo3.com for more information.');
        } catch (UnsupportedWebHookRequestException $e) {
            // Hook payload could not be identified as hook that should trigger rendering
            $manager->persist(
                (new HistoryEntry())
                    ->setType(HistoryEntryType::DOCS_RENDERING)
                    ->setStatus(DocsRenderingHistoryStatus::UNSUPPORTED_HOOK)
                    ->setData(
                        [
                            'type' => HistoryEntryType::DOCS_RENDERING,
                            'status' => DocsRenderingHistoryStatus::UNSUPPORTED_HOOK,
                            'headers' => $request->headers,
                            'payload' => $request->getContent(),
                            'triggeredBy' => HistoryEntryTrigger::API,
                            'exceptionCode' => $e->getCode(),
                            'user' => $userIdentifier
                        ]
                    )
            );
            $manager->flush();
            return new Response('Invalid hook payload. See https://intercept.typo3.com for more information.', Response::HTTP_PRECONDITION_FAILED);
        } catch (GitBranchDeletedException $e) {
            $manager->persist(
                (new HistoryEntry())
                    ->setType(HistoryEntryType::DOCS_RENDERING)
                    ->setStatus(DocsRenderingHistoryStatus::BRANCH_DELETED)
                    ->setData(
                        [
                            'type' => HistoryEntryType::DOCS_RENDERING,
                            'status' => DocsRenderingHistoryStatus::BRANCH_DELETED,
                            'triggeredBy' => HistoryEntryTrigger::API,
                            'exceptionCode' => $e->getCode(),
                            'exceptionMessage' => $e->getMessage(),
                            'user' => $userIdentifier
                        ]
                    )
            );
            $manager->flush();
            return new Response('The branch in this push event has been deleted.', Response::HTTP_PRECONDITION_FAILED);
        } catch (DocsNoRstChangesException $e) {
            $manager->persist(
                (new HistoryEntry())
                    ->setType(HistoryEntryType::DOCS_RENDERING)
                    ->setStatus(DocsRenderingHistoryStatus::BRANCH_NO_RST_CHANGES)
                    ->setData(
                        [
                            'type' => HistoryEntryType::DOCS_RENDERING,
                            'status' => DocsRenderingHistoryStatus::BRANCH_NO_RST_CHANGES,
                            'triggeredBy' => HistoryEntryTrigger::API,
                            'exceptionCode' => $e->getCode(),
                            'exceptionMessage' => $e->getMessage(),
                            'user' => $userIdentifier
                        ]
                    )
            );
            $manager->flush();
            return new Response(null, RESPONSE::HTTP_NO_CONTENT);
        }
    }
}
