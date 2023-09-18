<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Creator\GithubPullRequestCloseComment;
use App\Exception\DoNotCareException;
use App\Extractor\BambooBuildTriggered;
use App\Extractor\DeploymentInformation;
use App\Extractor\GithubCorePullRequest;
use App\Extractor\GithubPullRequestIssue;
use App\Extractor\GithubPushEventForCore;
use App\Extractor\GithubUserData;
use App\Extractor\GitPatchFile;
use App\Strategy\GithubRst\StrategyResolver;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;

/**
 * Fetch various detail information from GitHub.
 */
readonly class GithubService
{
    public function __construct(
        private string $pullRequestPatchPath,
        private ClientInterface $generalClient,
        private ClientInterface $githubClient,
        private StrategyResolver $strategyResolver,
        private string $accessKey
    ) {
    }

    /**
     * Get details of a new pull request issue on GitHub.
     *
     * @throws DoNotCareException
     */
    public function getIssueDetails(GithubCorePullRequest $pullRequest): GithubPullRequestIssue
    {
        return new GithubPullRequestIssue($this->generalClient->request('GET', $pullRequest->issueUrl));
    }

    /**
     * Get details of a GitHub user.
     *
     * @throws DoNotCareException
     */
    public function getUserDetails(GithubCorePullRequest $pullRequest): GithubUserData
    {
        return new GithubUserData($this->generalClient->request('GET', $pullRequest->userUrl));
    }

    /**
     * Fetch the diff file from a GitHub PR and store to disk.
     */
    public function getLocalDiff(GithubCorePullRequest $pullRequest): GitPatchFile
    {
        $response = $this->generalClient->request('GET', $pullRequest->diffUrl);
        $diff = (string) $response->getBody();
        if (!@mkdir($concurrentDirectory = $this->pullRequestPatchPath) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        $filePath = $this->pullRequestPatchPath . sha1($pullRequest->diffUrl);
        $patch = fopen($filePath, 'wb+');
        fwrite($patch, $diff);
        fclose($patch);

        return new GitPatchFile($filePath);
    }

    public function removeLocalDiff(GithubCorePullRequest $pullRequest): void
    {
        $filePath = $this->pullRequestPatchPath . sha1($pullRequest->diffUrl);
        @unlink($filePath);
    }

    /**
     * Close pull request, add a comment, lock pull request.
     */
    public function closePullRequest(
        GithubCorePullRequest $pullRequest,
        GithubPullRequestCloseComment $closeComment
    ): void {
        $client = $this->generalClient;

        $url = $pullRequest->pullRequestUrl;
        $client->request(
            'PATCH',
            $url,
            [
                'headers' => [
                    'Authorization' => 'token ' . $this->accessKey,
                ],
                'json' => [
                    'state' => 'closed',
                ],
            ]
        );

        $url = $pullRequest->commentsUrl;
        $client->request(
            'POST',
            $url,
            [
                'headers' => [
                    'Authorization' => 'token ' . $this->accessKey,
                ],
                'json' => [
                    'body' => $closeComment->comment,
                ],
            ]
        );

        $url = $pullRequest->issueUrl . '/lock';
        $client->request('PUT', $url, [
            'headers' => [
                'Authorization' => 'token ' . $this->accessKey,
            ],
        ]);
    }

    public function handleGithubIssuesForRstFiles(GithubPushEventForCore $pushEvent, string $githubChangelogToLogRepository): void
    {
        $added = $this->filterRstChanges($pushEvent->commit['added'] ?? []);
        $modified = $this->filterRstChanges($pushEvent->commit['modified'] ?? []);
        $removed = $this->filterRstChanges($pushEvent->commit['removed'] ?? []);
        if (0 === count($added) + count($modified) + count($removed)) {
            // no rst files changed, nothing to do
            return;
        }

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
            if (0 === count($files)) {
                continue;
            }

            $typeLabel = $typeLabels[$type];
            $body[] = sprintf("## %s\n", $typeLabel);

            $strategy = $this->strategyResolver->resolve($type);

            foreach ($files as $file) {
                try {
                    $response = $strategy->getFromGithub($pushEvent, $file);
                } catch (BadResponseException) {
                    continue;
                }

                $formattedContent = $strategy->formatResponse($response);
                if ('' !== $formattedContent) {
                    $version = basename(dirname($file));
                    $labels[] = $version;

                    $body[] = '<details>';
                    $body[] = sprintf("<summary>%s/%s</summary>\n\n", $version, basename($file));
                    $body[] = $formattedContent;
                    $body[] = '</details>' . "\n";
                }
            }
        }

        $requestUrl = sprintf('https://api.github.com/repos/%s/issues', $githubChangelogToLogRepository);
        $payload = [
            'title' => $pushEvent->headCommitTitle,
            'body' => implode("\n", $body),
            'labels' => array_values(array_unique($labels)),
        ];

        $this->generalClient->request('POST', $requestUrl, [
            'headers' => [
                'Authorization' => 'token ' . $this->accessKey,
            ],
            'json' => $payload,
        ]);
    }

    /**
     * Triggers new build in project TYPO3-Documentation/t3docs-ci-deploy.
     */
    public function triggerDocumentationPlan(DeploymentInformation $deploymentInformation): BambooBuildTriggered
    {
        $id = hash('xxh128', $deploymentInformation->packageName . $deploymentInformation->sourceBranch, false, ['secret' => random_bytes(256)]);
        $postBody = [
            'event_type' => 'render',
            'client_payload' => [
                'repository_url' => $deploymentInformation->repositoryUrl,
                'source_branch' => $deploymentInformation->sourceBranch,
                'target_branch_directory' => $deploymentInformation->targetBranchDirectory,
                'name' => $deploymentInformation->name,
                'vendor' => $deploymentInformation->vendor,
                'type_short' => $deploymentInformation->typeShort,
                'id' => $id,
            ],
        ];
        $this->githubClient->request(
            'POST',
            '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches',
            [
                'json' => $postBody,
            ]
        );

        return new BambooBuildTriggered(json_encode(['buildResultKey' => $id], JSON_THROW_ON_ERROR));
    }

    /**
     * Triggers new build in project TYPO3-Documentation/t3docs-ci-deploy for deletion.
     */
    public function triggerDocumentationDeletionPlan(DeploymentInformation $deploymentInformation): BambooBuildTriggered
    {
        $id = hash('xxh128', $deploymentInformation->packageName . $deploymentInformation->sourceBranch, false, ['secret' => random_bytes(256)]);
        $postBody = [
            'event_type' => 'delete',
            'client_payload' => [
                'target_branch_directory' => $deploymentInformation->targetBranchDirectory,
                'name' => $deploymentInformation->name,
                'vendor' => $deploymentInformation->vendor,
                'type_short' => $deploymentInformation->typeShort,
                'id' => $id,
            ],
        ];

        $this->githubClient->request(
            'POST',
            '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches',
            [
                'json' => $postBody,
            ]
        );

        return new BambooBuildTriggered(json_encode(['buildResultKey' => $id], JSON_THROW_ON_ERROR));
    }

    /**
     * Trigger new build of project CORE-DRD.
     */
    public function triggerDocumentationRedirectsPlan(): BambooBuildTriggered
    {
        $id = sha1((string) time());
        $postBody = [
            'event_type' => 'redirect',
            'client_payload' => [
                'id' => $id,
            ],
        ];
        $this->githubClient->request(
            'POST',
            '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches',
            [
                'json' => $postBody,
            ]
        );

        return new BambooBuildTriggered(json_encode(['buildResultKey' => $id], JSON_THROW_ON_ERROR));
    }

    /**
     * @param string[] $files
     *
     * @return string[]
     */
    private function filterRstChanges(array $files): array
    {
        return array_filter($files, static fn (string $file) => str_ends_with($file, '.rst') && str_contains($file, 'typo3/sysext/core/Documentation/Changelog/'));
    }
}
