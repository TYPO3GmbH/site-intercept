<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Client\GeneralClient;
use App\Creator\GithubPullRequestCloseComment;
use App\Exception\DoNotCareException;
use App\Extractor\GithubCorePullRequest;
use App\Extractor\GithubPullRequestIssue;
use App\Extractor\GithubUserData;
use App\Extractor\GitPatchFile;

/**
 * Fetch various detail information from github
 */
class GithubService
{
    /**
     * @var GeneralClient
     */
    private $client;

    /**
     * @var string Absolute path pull request files are put to
     */
    private $pullRequestPatchPath;

    /**
     * @var string Github access token
     */
    private $accessKey;

    /**
     * GithubService constructor.
     *
     * @param string $pullRequestPatchPath Absolute path pull request files are put to
     * @param GeneralClient $client General http client that does not need authentication
     */
    public function __construct(string $pullRequestPatchPath, GeneralClient $client)
    {
        $this->pullRequestPatchPath = $pullRequestPatchPath;
        $this->client = $client;
        $this->accessKey = getenv('GITHUB_ACCESS_TOKEN');
    }

    /**
     * Get details of a new pull request issue on github.
     *
     * @param GithubCorePullRequest $pullRequest
     * @return GithubPullRequestIssue
     * @throws DoNotCareException
     */
    public function getIssueDetails(GithubCorePullRequest $pullRequest): GithubPullRequestIssue
    {
        return new GithubPullRequestIssue($this->client->get($pullRequest->issueUrl));
    }

    /**
     * Get details of a github user.
     *
     * @param GithubCorePullRequest $pullRequest
     * @return GithubUserData
     * @throws DoNotCareException
     */
    public function getUserDetails(GithubCorePullRequest $pullRequest): GithubUserData
    {
        return new GithubUserData($this->client->get($pullRequest->userUrl));
    }

    /**
     * Fetch the diff file from a github PR and store to disk
     *
     * @param GithubCorePullRequest $pullRequest
     * @return GitPatchFile
     */
    public function getLocalDiff(GithubCorePullRequest $pullRequest): GitPatchFile
    {
        $response = $this->client->get($pullRequest->diffUrl);
        $diff = (string)$response->getBody();
        if (!mkdir($concurrentDirectory = $this->pullRequestPatchPath) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        $filePath = $this->pullRequestPatchPath . sha1($pullRequest->diffUrl);
        $patch = fopen($filePath, 'w+');
        fwrite($patch, $diff);
        fclose($patch);
        return new GitPatchFile($filePath);
    }

    /**
     * Close pull request, add a coment, lock pull request
     *
     * @param GithubCorePullRequest $pullRequest
     * @param GithubPullRequestCloseComment $closeComment
     */
    public function closePullRequest(
        GithubCorePullRequest $pullRequest,
        GithubPullRequestCloseComment $closeComment
    ): void {
        $client = $this->client;

        $url = $pullRequest->pullRequestUrl . '?access_token=' . $this->accessKey;
        $client->patch(
            $url,
            [
                'json' => [
                    'state' => 'closed',
                ]
            ]
        );

        $url = $pullRequest->commentsUrl . '?access_token=' . $this->accessKey;
        $client->post(
            $url,
            [
                'json' => [
                    'body' => $closeComment->comment,
                ],
            ]
        );

        $url = $pullRequest->issueUrl . '/lock?access_token=' . $this->accessKey;
        $client->put($url);
    }
}
