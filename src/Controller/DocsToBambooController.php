<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Exception\UnsupportedWebHookRequestException;
use App\Service\BambooService;
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
class DocsToBambooController extends AbstractController
{
    /**
     * @Route("/docs", name="docs_to_bamboo")
     * @Route("/", host="docs-hook.typo3.org", name="docs_hook_to_bamboo")
     * @param Request $request
     * @param BambooService $bambooService
     * @param WebHookService $webhookService
     * @param LoggerInterface $logger
     * @return Response
     */
    public function index(Request $request, BambooService $bambooService, WebHookService $webhookService, LoggerInterface $logger): Response
    {
        try {
            $bambooService->triggerDocumentationPlan($webhookService->createPushEvent($request));
        } catch (UnsupportedWebHookRequestException $e) {
            // Hook payload could not be identified as hook that
            // should trigger rendering
            $logger->info($e->getMessage(), ['headers' => $request->headers, 'payload' => $request->getContent()]);
        }
        return Response::create();
    }
}
