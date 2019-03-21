<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Exception\DoNotCareException;
use App\Extractor\GithubPushEventForDocs;
use App\Service\BambooService;
use App\Service\DocumentationBuildInformationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Trigger documentation rendering from a github hook that calls
 * https://docs-hook.typo3.org/ or /docs/ route
 */
class DocsToBambooController extends AbstractController
{
    /**
     * @Route("/docs", name="docs_to_bamboo")
     * @Route("/", host="docs-hook.typo3.org", name="docs_hook_to_bamboo")
     * @param Request $request
     * @param BambooService $bambooService
     * @param DocumentationBuildInformationService $documentationBuildInformationService
     * @return Response
     */
    public function index(Request $request, BambooService $bambooService, DocumentationBuildInformationService $documentationBuildInformationService): Response
    {
        try {
            $pushEventInformation = new GithubPushEventForDocs($request->getContent());
            $documentationBuildInformation = $documentationBuildInformationService->generateBuildInformation($pushEventInformation);
            $bambooService->triggerDocumentationPlan($documentationBuildInformation);
        } catch (DoNotCareException $e) {
            // Hook payload could not be identified as hook that
            // should trigger rendering
        }
        return Response::create();
    }
}
