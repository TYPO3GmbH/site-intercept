<?php
declare(strict_types = 1);

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
use App\GitWrapper\Event\GitOutputListener;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symplify\GitWrapper\EventSubscriber\GitLoggerEventSubscriber;
use Symplify\GitWrapper\GitWorkingCopy;
use Symplify\GitWrapper\GitWrapper;

/**
 * Apply patches to local core checkout and push to gerrit
 *
 * @codeCoverageIgnore GitWrapper and friends are unmockable due to final keyword :(
 */
class LocalCoreGitService
{
    private GitWorkingCopy $workingCopy;

    private GitOutputListener $listener;

    /**
     * Prepare core checkout.
     *
     * @param LoggerInterface $logger
     * @param GitOutputListener $listener
     * @param string $pullRequestCorePath Absolute path of local core git checkout
     */
    public function __construct(LoggerInterface $logger, GitOutputListener $listener, string $pullRequestCorePath)
    {
        $this->listener = $listener;
        $gitWrapper = new GitWrapper();
        $gitWrapper->setEnvVar('HOME', getenv('GIT_HOME'));
        $gitWrapper->setPrivateKey(getenv('GIT_SSH_PRIVATE_KEY'));
        $gitWrapper->addLoggerEventSubscriber(new GitLoggerEventSubscriber($logger));
        // Increase timeout to have a chance initial clone runs through
        $gitWrapper->setTimeout(300);
        $this->workingCopy = $gitWrapper->workingCopy($pullRequestCorePath);
        if (!$this->workingCopy->isCloned()) {
            // Initial clone
            $this->workingCopy->cloneRepository('git://git.typo3.org/Packages/TYPO3.CMS.git');
            $this->workingCopy->setCloned(true);
            // Enable commit hook
            $filesystem = new Filesystem();
            $filesystem->copy(
                $pullRequestCorePath . 'Build/git-hooks/commit-msg',
                $pullRequestCorePath . '.git/hooks/commit-msg'
            );
        }
    }

    /**
     * Commit a patch to the local git repository.
     *
     * @param GitPatchFile $patchFile
     * @param GithubCorePullRequest $pullRequest
     * @param GithubUserData $userData
     * @param GerritCommitMessage $commitMessage
     */
    public function commitPatchAsUser(
        GitPatchFile $patchFile,
        GithubCorePullRequest $pullRequest,
        GithubUserData $userData,
        GerritCommitMessage $commitMessage
    ): void {
        $workingCopy = $this->workingCopy;
        $workingCopy->clean('-d', '-f');
        $workingCopy->reset('--hard');
        $workingCopy->checkout($pullRequest->branch);
        $workingCopy->reset('--hard', 'origin/' . $pullRequest->branch);
        $workingCopy->fetch();
        if (!$workingCopy->isUpToDate()) {
            $workingCopy->pull();
        }
        $workingCopy->apply($patchFile->file);
        $workingCopy->add('.');
        $workingCopy->commit([
            'author' => '"' . $userData->user . '<' . $userData->email . '>"',
            'm' => $commitMessage->message,
            'verbose' => true,
        ]);
    }

    /**
     * Push the prepared patch on local git repository to gerrit remote.
     *
     * @param GithubCorePullRequest $pullRequest
     * @return GitPushOutput
     */
    public function pushToGerrit(GithubCorePullRequest $pullRequest): GitPushOutput
    {
        $wrapper = $this->workingCopy->getWrapper();
        $wrapper->addOutputEventSubscriber($this->listener);
        $this->workingCopy->push('origin', 'HEAD:refs/for/' . $pullRequest->branch);
        $wrapper->removeOutputEventSubscriber($this->listener);
        return new GitPushOutput($this->listener->output);
    }
}
