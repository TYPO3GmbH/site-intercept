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
use App\Extractor\GithubPushEventForCore;
use App\Service\GithubService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for Github rst files issue creation.
 * Triggered by github hook on https://github.com/TYPO3/typo3.
 */
class GithubRstIssueController extends AbstractController
{
    /**
     * Called by github post merge, this checks the incoming merge for rst file changes and posts them on a Github repository as issues
     * @Route("/create-rst-issue", name="docs_github_rst_issue_create", methods={"POST"})
     *
     * @param Request $request
     * @param GithubService $githubService
     * @throws \JsonException
     * @return Response
     */
    public function index(Request $request, GithubService $githubService, string $githubChangelogToLogRepository): Response
    {
        try {
            $pushEventInformation = new GithubPushEventForCore(json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR));
            if ($pushEventInformation->targetBranch !== 'main') {
                // We do not care about backported changes
                throw new DoNotCareException();
            }
            $githubService->handleGithubIssuesForRstFiles($pushEventInformation, $githubChangelogToLogRepository);
        } catch (DoNotCareException $e) {
            // Hook payload could not be identified as hook that should trigger git split
        } catch (\JsonException $e) {
            return new Response('Invalid JSON', 400);
        }

        return new Response();
    }
}
