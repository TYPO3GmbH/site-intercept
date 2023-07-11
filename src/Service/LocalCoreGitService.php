<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Creator\GerritCommitMessage;
use App\Extractor\GithubCorePullRequest;
use App\Extractor\GithubUserData;
use App\Extractor\GitPatchFile;
use App\Extractor\GitPushOutput;
use Gitonomy\Git\Repository;

/**
 * Apply patches to local core checkout and push to gerrit.
 *
 * @codeCoverageIgnore GitWrapper and friends are unmockable due to final keyword :(
 */
class LocalCoreGitService
{
    private ?Repository $repository = null;

    public function __construct(
        private readonly GitService $gitService,
        private readonly string $pullRequestCorePath
    ) {
    }

    /**
     * Commit a patch to the local git repository.
     */
    public function commitPatchAsUser(
        GitPatchFile $patchFile,
        GithubCorePullRequest $pullRequest,
        GithubUserData $userData,
        GerritCommitMessage $commitMessage
    ): void {
        $repository = $this->gitService->cloneAndCheckout($this->getRepository(), 'git@github.com:typo3/typo3.git', 'main');
        $this->enableCommitHook($repository);

        $repository->run('clean', ['-d', '-f']);
        $repository->run('reset', ['--hard']);
        $repository->run('checkout', [$pullRequest->branch]);
        $repository->run('reset', ['--hard', 'origin/' . $pullRequest->branch]);
        $repository->run('pull', ['--rebase']);
        $repository->run('apply', [$patchFile->file]);
        $repository->run('add', [$patchFile->file]);
        $repository->run('commit', [
            'author' => '"' . $userData->user . '<' . $userData->email . '>"',
            'm' => $commitMessage->message,
            'verbose' => true,
        ]);
    }

    /**
     * Push the prepared patch on local git repository to gerrit remote.
     */
    public function pushToGerrit(GithubCorePullRequest $pullRequest): GitPushOutput
    {
        $output = $this->getRepository()->run('push', ['origin', 'HEAD:refs/for/' . $pullRequest->branch]);

        return new GitPushOutput($output);
    }

    private function getRepository(): Repository
    {
        if (null === $this->repository) {
            $this->repository = $this->gitService->getRepository($this->pullRequestCorePath);
        }

        return $this->repository;
    }

    private function enableCommitHook(Repository $repository): void
    {
        $hooks = $repository->getHooks();
        if (!$hooks->has('commit-msg')) {
            $hooks->setSymlink('commit-msg', $this->pullRequestCorePath . 'Build/git-hooks/commit-msg');
        }
    }
}
