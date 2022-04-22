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
use App\Client\GithubClient;
use App\Creator\GithubPullRequestCloseComment;
use App\Exception\DoNotCareException;
use App\Extractor\BambooBuildTriggered;
use App\Extractor\DeploymentInformation;
use App\Extractor\GithubCorePullRequest;
use App\Extractor\GithubPullRequestIssue;
use App\Extractor\GithubPushEventForCore;
use App\Extractor\GithubUserData;
use App\Extractor\GitPatchFile;
use GuzzleHttp\Exception\BadResponseException;
use RuntimeException;

/**
 * Fetch various detail information from github
 */
class GithubService
{
    private GeneralClient $client;

    /**
     * @var string Absolute path pull request files are put to
     */
    private string $pullRequestPatchPath;

    /**
     * @var string Github access token
     */
    private $accessKey;
    private GithubClient $githubClient;

    /**
     * GithubService constructor.
     *
     * @param string $pullRequestPatchPath Absolute path pull request files are put to
     * @param GeneralClient $client General http client that does not need authentication
     */
    public function __construct(string $pullRequestPatchPath, GeneralClient $client, GithubClient $githubClient)
    {
        $this->pullRequestPatchPath = $pullRequestPatchPath;
        $this->client = $client;
        $this->accessKey = $_ENV['GITHUB_ACCESS_TOKEN'] ?? '';
        $this->githubClient = $githubClient;
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
        if (!@mkdir($concurrentDirectory = $this->pullRequestPatchPath) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        $filePath = $this->pullRequestPatchPath . sha1($pullRequest->diffUrl);
        $patch = fopen($filePath, 'w+');
        fwrite($patch, $diff);
        fclose($patch);
        return new GitPatchFile($filePath);
    }

    /**
     * Close pull request, add a comment, lock pull request
     *
     * @param GithubCorePullRequest $pullRequest
     * @param GithubPullRequestCloseComment $closeComment
     */
    public function closePullRequest(
        GithubCorePullRequest $pullRequest,
        GithubPullRequestCloseComment $closeComment
    ): void {
        $client = $this->client;

        $url = $pullRequest->pullRequestUrl;
        $client->patch(
            $url,
            [
                'headers' => [
                    'Authorization' => 'token ' . $this->accessKey
                ],
                'json' => [
                    'state' => 'closed',
                ]
            ]
        );

        $url = $pullRequest->commentsUrl;
        $client->post(
            $url,
            [
                'headers' => [
                    'Authorization' => 'token ' . $this->accessKey
                ],
                'json' => [
                    'body' => $closeComment->comment,
                ],
            ]
        );

        $url = $pullRequest->issueUrl . '/lock';
        $client->put($url, [
            'headers' => [
                'Authorization' => 'token ' . $this->accessKey
            ],
        ]);
    }

    public function handleGithubIssuesForRstFiles(GithubPushEventForCore $pushEvent, string $githubChangelogToLogRepository): void
    {
        $added = $this->filterRstChanges($pushEvent->commit['added'] ?? []);
        $modified = $this->filterRstChanges($pushEvent->commit['modified'] ?? []);
        $removed = $this->filterRstChanges($pushEvent->commit['removed'] ?? []);
        if (count($added) + count($modified) + count($removed) === 0) {
            // no rst files changed, nothing to do
            return;
        }
        $githubRawBaseUrl = sprintf('https://raw.githubusercontent.com/%s/%s', $pushEvent->repositoryFullName, $pushEvent->commit['id']);

        $changedDocuments = [
            'added' => $added,
            'modified' => $modified,
            'removed' => $removed,
        ];
        $typeLabels = [
            'added' => ':heavy_plus_sign: Added files',
            'modified' => ':heavy_division_sign: Modified files',
            'removed' => ':heavy_minus_sign: Removed files',
        ];

        $body = [];
        $body[] = sprintf(':information_source: View this commit [on Github](%s)', $pushEvent->commit['url']);
        $body[] = sprintf(':busts_in_silhouette: Authored by %s %s', $pushEvent->commit['author']['name'], $pushEvent->commit['author']['email']);
        $body[] = sprintf(":heavy_check_mark: Merged by %s %s\n", $pushEvent->commit['committer']['name'], $pushEvent->commit['committer']['email']);
        $body[] = "## Commit message\n";
        $body[] = sprintf("%s\n", $pushEvent->commit['message']);

        $labels = [];

        foreach ($changedDocuments as $type => $files) {
            if (count($files) === 0) {
                continue;
            }

            $typeLabel = $typeLabels[$type];
            $body[] = sprintf("## %s\n", $typeLabel);

            foreach ($files as $file) {
                $fullRawUrl = sprintf('%s/%s', $githubRawBaseUrl, $file);
                try {
                    $response = $this->client->request('GET', $fullRawUrl);
                } catch (BadResponseException $e) {
                    continue;
                }
                $changelogContent = (string)$response->getBody();

                $version = basename(dirname($file));
                $labels[] = $version;

                $body[] = '<details>';
                $body[] = sprintf("<summary>%s/%s</summary>\n\n", $version, basename($file));
                $body[] = sprintf("```rst\n%s\n```\n", $changelogContent);
                $body[] = '</details>' . "\n";
            }
        }

        $requestUrl = sprintf('https://api.github.com/repos/%s/issues', $githubChangelogToLogRepository);
        $payload = [
            'title' => $pushEvent->headCommitTitle,
            'body' => implode("\n", $body),
            'labels' => array_values(array_unique($labels)),
        ];

        $this->client->request('POST', $requestUrl, [
            'headers' => [
                'Authorization' => 'token ' . $this->accessKey
            ],
            'json' => $payload,
        ]);
    }

    /**
     * Triggers new build in project TYPO3-Documentation/t3docs-ci-deploy
     *
     * @param DeploymentInformation $deploymentInformation
     * @return BambooBuildTriggered
     */
    public function triggerDocumentationPlan(DeploymentInformation $deploymentInformation): BambooBuildTriggered
    {
        $id = sha1((string)(time()) . $deploymentInformation->packageName);
        $postBody = [
            'event_type' => 'render',
            'client_payload' => [
                'repository_url' => $deploymentInformation->repositoryUrl,
                'source_branch' => $deploymentInformation->sourceBranch,
                'target_branch_directory' => $deploymentInformation->targetBranchDirectory,
                'name' => $deploymentInformation->name,
                'vendor' => $deploymentInformation->vendor,
                'type_short' => $deploymentInformation->typeShort,
                'id' => $id
            ]
        ];
        $this->githubClient->post(
            '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches',
            [
                'json' => $postBody
            ]
        );
        return new BambooBuildTriggered(json_encode(['buildResultKey' => $id]));
    }

    /**
     * Triggers new build in project TYPO3-Documentation/t3docs-ci-deploy for deletion
     *
     * @param DeploymentInformation $deploymentInformation
     * @return BambooBuildTriggered
     */
    public function triggerDocumentationDeletionPlan(DeploymentInformation $deploymentInformation): BambooBuildTriggered
    {
        $id = sha1((string)time() . $deploymentInformation->packageName);
        $postBody = [
            'event_type' => 'delete',
            'client_payload' => [
                'target_branch_directory' => $deploymentInformation->targetBranchDirectory,
                'name' => $deploymentInformation->name,
                'vendor' => $deploymentInformation->vendor,
                'type_short' => $deploymentInformation->typeShort,
                'id' => $id
            ]
        ];

        $this->githubClient->post(
            '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches',
            [
                'json' => $postBody
            ]
        );
        return new BambooBuildTriggered(json_encode(['buildResultKey' => $id]));
    }

    /**
     * Trigger new build of project CORE-DRD
     *
     * @return BambooBuildTriggered
     */
    public function triggerDocumentationRedirectsPlan(): BambooBuildTriggered
    {
        $id = sha1((string)time());
        $postBody = [
            'event_type' => 'redirect',
            'client_payload' => [
                'id' => $id
            ]
        ];
        $this->githubClient->post(
            '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches',
            [
                'json' => $postBody
            ]
        );
        return new BambooBuildTriggered(json_encode(['buildResultKey' => $id]));
    }

    /**
     * @param string[] $files
     * @return string[]
     */
    private function filterRstChanges(array $files): array
    {
        return array_filter($files, static function (string $file) {
            return str_ends_with($file, '.rst');
        });
    }
}
