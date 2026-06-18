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
use App\Enum\DocsRenderingHistoryStatus;
use App\Enum\DocumentationRenderingTrigger;
use App\Enum\HistoryEntryTrigger;
use App\Enum\HistoryEntryType;
use App\Exception\DocsNoRstChangesException;
use App\Exception\DocumentationRenderingRequestDeclinedException;
use App\Exception\GitBranchDeletedException;
use App\Exception\GithubHookPingException;
use App\Exception\UnsupportedWebHookRequestException;
use App\Service\HistoryService;
use App\Service\RenderDocumentationService;
use App\Service\WebHookService;
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
    public function __construct(
        private readonly WebHookService $webhookService,
        private readonly HistoryService $historyService,
        private readonly RenderDocumentationService $renderDocumentationService,
    ) {
    }

    #[Route(path: '/docs', name: 'docs_to_bamboo')]
    #[Route(path: '/', name: 'docs_hook_to_bamboo', host: 'docs-hook.typo3.org')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $userIdentifier = 'Anon.';
        if ($user instanceof KeyCloakUser) {
            $userIdentifier = $user->getDisplayName();
        }
        try {
            $pushEvents = $this->webhookService->createPushEvent($request);
            $errorMessages = [];

            foreach ($pushEvents as $pushEvent) {
                try {
                    $this->renderDocumentationService->requestDocumentationRendering($pushEvent, DocumentationRenderingTrigger::API);
                } catch (DocumentationRenderingRequestDeclinedException $e) {
                    $errorMessages[] = $e->getMessage();
                }
            }
            if ([] !== $errorMessages) {
                return new Response(implode("\n", $errorMessages), Response::HTTP_PRECONDITION_FAILED);
            }

            return new Response(null, Response::HTTP_NO_CONTENT);
        } catch (GithubHookPingException $e) {
            // Hook payload is a 'GitHub ping' - log that as 'info / success' with the
            // url that hook came from. This is triggered by GitHub when a new web hook is added,
            // we want to be nice and make this one succeed.
            $this->historyService->writeHistory(new HistoryEntryDto(
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
            $this->historyService->writeHistory(new HistoryEntryDto(
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
            $this->historyService->writeHistory(new HistoryEntryDto(
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
            $this->historyService->writeHistory(new HistoryEntryDto(
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
