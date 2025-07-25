<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Creator\GerritCommitMessage;
use App\Creator\GithubPullRequestCloseComment;
use App\Exception\DoNotCareException;
use App\Extractor\GithubCorePullRequest;
use App\Service\ForgeService;
use App\Service\GithubService;
use App\Service\LocalCoreGitService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Transform GitHub core pull requests from https://github.com/TYPO3/TYPO3.CMS
 * into a gerrit patch, create a forge issue, close GitHub pr with some happy
 * little message.
 */
class GithubPullRequestController extends AbstractController
{
    /**
     * Called by GitHub for new pull requests on.
     */
    #[Route(path: '/githubpr', name: 'core_git_pr')]
    public function index(
        Request $request,
        GithubService $githubService,
        ForgeService $forgeService,
        LocalCoreGitService $gitService
    ): Response {
        try {
            $pullRequest = new GithubCorePullRequest($request->getContent());
            $issueDetails = $githubService->getIssueDetails($pullRequest);
            $userDetails = $githubService->getUserDetails($pullRequest);
            $forgeIssue = $forgeService->createIssue($issueDetails);
            $gerritCommitMessage = new GerritCommitMessage($issueDetails, $forgeIssue);
            $localDiffFile = $githubService->getLocalDiff($pullRequest);
            $gitService->commitPatchAsUser($localDiffFile, $pullRequest, $userDetails, $gerritCommitMessage);
            $gitPushOutput = $gitService->pushToGerrit($pullRequest);
            $closeComment = new GithubPullRequestCloseComment($gitPushOutput);
            $githubService->closePullRequest($pullRequest, $closeComment);
            $githubService->removeLocalDiff($pullRequest);
        } catch (DoNotCareException) {
            // Hook payload could not be identified as hook that
            // should trigger a transfer of this PR
        }

        return new Response('', Response::HTTP_OK);
    }
}
