<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Exception\DoNotCareException;
use App\Extractor\GithubPushEventForCore;
use App\Service\GithubService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for GitH1ub rst files issue creation.
 * Triggered by GitHub hook on https://github.com/TYPO3/typo3.
 */
class GithubRstIssueController extends AbstractController
{
    /**
     * Called by GitHub post merge, this checks the incoming merge for rst file changes and posts them on a GitHub repository as issues.
     *
     * @throws \JsonException
     */
    #[Route(path: '/create-rst-issue', name: 'docs_github_rst_issue_create', methods: ['POST'])]
    public function index(Request $request, GithubService $githubService, string $githubChangelogToLogRepository): Response
    {
        try {
            $pushEventInformation = new GithubPushEventForCore(json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR));
            if ('main' !== $pushEventInformation->targetBranch) {
                // We do not care about backport changes
                throw new DoNotCareException();
            }
            $githubService->handleGithubIssuesForRstFiles($pushEventInformation, $githubChangelogToLogRepository);
        } catch (DoNotCareException) {
            // Hook payload could not be identified as hook that should trigger git split
        } catch (\JsonException) {
            return new Response('Invalid JSON', \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);
        }

        return new Response();
    }
}
