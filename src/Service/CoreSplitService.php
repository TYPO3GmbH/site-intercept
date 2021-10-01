<?php

declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\HistoryEntry;
use App\Enum\HistoryEntryTrigger;
use App\Enum\HistoryEntryType;
use App\Enum\SplitterStatus;
use App\Extractor\GithubPushEventForCore;
use App\GitWrapper\Event\GitOutputListener;
use App\Utility\RepositoryUrlUtility;
use Doctrine\ORM\EntityManagerInterface;
use PhpAmqpLib\Wire\IO\AbstractIO;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symplify\GitWrapper\Exception\GitException;
use Symplify\GitWrapper\GitWorkingCopy;
use Symplify\GitWrapper\GitWrapper;

/**
 * Split mono repo TYPO3.CMS to single repos per extension
 *
 * @codeCoverageIgnore Covered by integration tests
 */
class CoreSplitService
{
    private LoggerInterface $logger;

    /**
     * @var string Absolute path to core checkout for core split jobs
     */
    private string $splitCorePath;

    /**
     * @var string Link to github mono repo, eg. 'git@github.com:TYPO3/TYPO3.CMS.git'
     */
    private string $splitMonoRepo;

    /**
     * @var string Base link to github single repos, eg. 'git@github.com:TYPO3-CMS/'
     */
    private string $splitSingleRepoBase;

    /**
     * @var string Absolute path to extensions checkout for TAG jobs
     */
    private string $splitSingleRepoPath;

    /**
     * @var GitOutputListener An objects catching git stdout responses
     */
    private GitOutputListener $gitOutputListener;

    /**
     * @var array An array filled by integration test to run only a gives series of extensions
     */
    private array $overrideExtensionList = [];

    /**
     * @var GithubPushEventForCore Runtime rabbit message
     */
    private GithubPushEventForCore $event;
    private EntityManagerInterface $entityManager;

    /**
     * @param LoggerInterface $logger
     * @param string $splitCorePath
     * @param string $splitMonoRepo
     * @param string $splitSingleRepoBase
     * @param string $splitSingleRepoPath
     */
    public function __construct(
        LoggerInterface $logger,
        string $splitCorePath,
        string $splitMonoRepo,
        string $splitSingleRepoBase,
        string $splitSingleRepoPath,
        GitOutputListener $gitOutputListener,
        EntityManagerInterface $entityManager
    ) {
        $this->logger = $logger;
        $this->splitCorePath = $splitCorePath;
        $this->splitMonoRepo = $splitMonoRepo;
        $this->splitSingleRepoBase = $splitSingleRepoBase;
        $this->splitSingleRepoPath = $splitSingleRepoPath;
        $this->gitOutputListener = $gitOutputListener;
        $this->entityManager = $entityManager;
    }

    /**
     * @codeCoverageIgnore This is just a wrapper method to get a substring of a protected property
     */
    public function getRepositoryName(): string
    {
        return RepositoryUrlUtility::extractRepositoryNameFromCloneUrl($this->splitMonoRepo);
    }

    /**
     * Execute core splitting
     *
     * @param GithubPushEventForCore $event
     * @param AbstractIO $rabbitIO
     */
    public function split(GithubPushEventForCore $event, AbstractIO $rabbitIO): void
    {
        $this->event = $event;

        $gitWrapper = new GitWrapper();
        $gitWrapper->setEnvVar('HOME', $_ENV['GIT_HOME'] ?? '');
        $gitWrapper->setPrivateKey($_ENV['GIT_SSH_PRIVATE_KEY'] ?? '');
        // Increase timeout to have a chance initial clone runs through
        $gitWrapper->setTimeout(300);
        $workingCopy = $gitWrapper->workingCopy($this->splitCorePath);

        $this->initialCloneAndCheckout($workingCopy, $event->sourceBranch);
        // Send a heartbeat after initial clone
        $rabbitIO->read(0);

        $splitBinary = $this->getSplitBinary();
        $extensions = $this->getExtensions();
        $this->writeHistoryEntry('Extensions to split: ' . implode(' ', $extensions));

        // Add remotes per extension if needed and fetch them. Note this is
        // different in the tagger: The splitter works on additional remotes in
        // main directory, the tagger works on clones of the extensions in own directories
        $existingRemotes = explode(chr(10), $this->gitCommand($workingCopy, true, 'remote'));
        foreach ($extensions as $extension) {
            $fullRemotePath = $this->splitSingleRepoBase . $extension . '.git';
            $this->writeHistoryEntry('Fetching extension ' . $extension . ' from ' . $fullRemotePath);
            if (!in_array($extension, $existingRemotes)) {
                $this->gitCommand($workingCopy, false, 'remote', 'add', $extension, $this->splitSingleRepoBase . $extension . '.git');
            }
            $this->gitCommand($workingCopy, false, 'fetch', $extension);
            // Send a heartbeat after each fetch
            $rabbitIO->read(0);
        }

        // Split and push
        foreach ($extensions as $extension) {
            $execOutput = [];
            $execExitCode = 0;
            $command = 'cd ' . escapeshellarg($this->splitCorePath) . ' && '
                       . escapeshellcmd('../../bin/' . $splitBinary)
                       . ' --prefix=' . escapeshellarg('typo3/sysext/' . $extension)
                       . ' --origin=' . escapeshellarg('origin/' . $event->sourceBranch)
                       . ' 2>&1';
            $splitSha = exec($command, $execOutput, $execExitCode);
            $this->writeHistoryEntry('Split operation extension "' . $extension . '" result "' . $execExitCode . '" with sha "' . $splitSha . '" Full output: "' . implode($execOutput) . '"');
            if ($execExitCode !== 0) {
                throw new \RuntimeException('Splitting went wrong. Aborting.');
            }
            $remoteRef = $splitSha . ':refs/heads/' . $event->targetBranch;
            $this->writeHistoryEntry('Pushing extension "' . $extension . '" to remote ' . $remoteRef);
            $this->gitCommand($workingCopy, false, 'push', $extension, $remoteRef);
            // Send a heartbeat after each push
            $rabbitIO->read(0);
        }
    }

    /**
     * Tag sub tree repositories
     *
     * @param GithubPushEventForCore $event
     * @param AbstractIO $rabbitIO
     */
    public function tag(GithubPushEventForCore $event, AbstractIO $rabbitIO): void
    {
        $this->event = $event;

        // If given tag does not start with "v" ... ignore this job
        if (strpos($event->tag, 'v') !== 0) {
            $this->writeHistoryEntry(
                'Job ignored: The tagger only handles tags starting with "v", "' . $event->tag . '" given.',
                'WARNING'
            );
            return;
        }

        $coreGitWrapper = new GitWrapper();
        $coreGitWrapper->setEnvVar('HOME', $_ENV['GIT_HOME'] ?? '');
        $coreGitWrapper->setPrivateKey($_ENV['GIT_SSH_PRIVATE_KEY'] ?? '');
        // Increase timeout to have a chance initial clone runs through
        $coreGitWrapper->setTimeout(300);
        $coreWorkingCopy = $coreGitWrapper->workingCopy($this->splitCorePath);

        $this->initialCloneAndCheckout($coreWorkingCopy, $event->sourceBranch);
        // Send a heartbeat after initial clone
        $rabbitIO->read(0);

        // Verify given tag is in one of the branches we DO consider: TYPO3_8_7, 9.x, 10.x, ..., master)
        try {
            $branchesContainTag = $this->gitCommand($coreWorkingCopy, false, 'branch', '-r', '--contains', $event->tag);
        } catch (GitException $e) {
            $this->writeHistoryEntry('Job ignored: No branch contains given tag "' . $event->tag . '"', 'WARNING');
            return;
        }
        $branchesContainTag = explode(chr(10), $branchesContainTag);
        $responsibleForBranch = false;
        foreach ($branchesContainTag as $branch) {
            $branch = trim($branch);
            if (empty($branch) || strpos($branch, 'origin/') !== 0 || strpos($branch, 'origin/HEAD') === 0) {
                continue;
            }
            $sourceBranch = str_replace('origin/', '', $branch);
            if ($sourceBranch === $event->sourceBranch || (int)trim(substr($sourceBranch, 0, 2), '.') >= 9) {
                $responsibleForBranch = true;
                break;
            }
        }
        if (!$responsibleForBranch) {
            $this->writeHistoryEntry(
                'Job ignored: Skipped tagging sub tree repositories: The given tag "' . $event->tag . '" is not in one of the branches we do care of - "TYPO3_8-7", ">=9.x.y" or "master"',
                'WARNING'
            );
            return;
        }

        // Check out this tag to get a list of extensions
        $this->checkoutDetachedHead($coreWorkingCopy, $event->tag);
        $extensions = $this->getExtensions();

        // Send a heartbeat after checkout
        $rabbitIO->read(0);

        // Make sure base extension path exists
        if (!mkdir($concurrentDirectory = $this->splitSingleRepoPath) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        foreach ($extensions as $extension) {
            $extensionWorkingCopy = $this->initialExtensionWorkingCopyOrUpdate($extension);

            // Resolve tree-hashes of main repository
            $revListOfTagInMainRepoForExtension = $this->gitCommand($coreWorkingCopy, true, 'rev-list', $event->tag, 'typo3/sysext/' . $extension);
            $revListOfTagInMainRepoForExtension = explode(chr(10), $revListOfTagInMainRepoForExtension);
            $mainRepoTreeHashes = [];
            foreach ($revListOfTagInMainRepoForExtension as $rev) {
                if (empty($rev)) {
                    continue;
                }
                $treeHash = $this->gitCommand($coreWorkingCopy, true, 'ls-tree', $rev, 'typo3/sysext/' . $extension);
                if (empty($treeHash)) {
                    // This one may look weird but is ok: There ARE commits (rev-list) for single directories, that
                    // do NOT have a tree attached (ls-tree) to that commit in the very same directory.
                    // This happens if a commit removes all files from an extension directory
                    // ("git mv typo3/sysext/install typo3/sysext/install_old"). The according tree
                    // object then simply does not have files attached. If then later that commit is reverted
                    // or the extension re-introduced and used here for tagging, the ls-tree for these commit has
                    // no tree objects and ls-tree is empty. It is safe to ignore these cases and continue.
                    // Examples commit hashes for those cases:
                    // a658209aad3f6904017e08f57cc32ce6a97c065f  -  typo3/sysext/filelist gone, later re-introduced
                    // b4e6274841f9f0f33779bfb14afe2fa1237bb27a  -  typo3/sysext/install gone, later re-introduced
                    // 09e85a95861ae41e83749f1aed6693748060a920  -  typo3/sysext/workspaces gone, later re-introduced
                    continue;
                }
                preg_match('([0-9a-f]{40})', $treeHash, $matches);
                if (empty($matches[0])) {
                    throw new \RuntimeException('Something went wrong calculating tree hashes');
                }
                $mainRepoTreeHashes[] = $matches[0];
            }

            // Get commit and tree hashes of extension repo
            $extensionCommitsAndTreeHashesRaw = $this->gitCommand($extensionWorkingCopy, true, 'rev-list', '--all', '--pretty=%H %T');
            $extensionCommitsAndTreeHashesRaw = explode(chr(10), $extensionCommitsAndTreeHashesRaw);
            $extensionCommitsAndTreeHashes = [];
            foreach ($extensionCommitsAndTreeHashesRaw as $commitAndTreeHash) {
                if (empty($commitAndTreeHash) || strpos($commitAndTreeHash, 'commit ') === 0) {
                    continue;
                }
                $commitAndTreeHash = explode(' ', $commitAndTreeHash);
                $extensionCommitsAndTreeHashes[$commitAndTreeHash[0]] = $commitAndTreeHash[1];
            }

            // Find closest commit-hash having both the same tree-hash in the main and package repository
            $foundCommitHash = '';
            foreach ($mainRepoTreeHashes as $mainRepoTreeHash) {
                foreach ($extensionCommitsAndTreeHashes as $extensionCommitHash => $extensionTreeHash) {
                    if ($mainRepoTreeHash === $extensionTreeHash) {
                        $foundCommitHash = $extensionCommitHash;
                        break 2;
                    }
                }
            }

            if (empty($foundCommitHash)) {
                throw new \RuntimeException('Unable to match core tree hashes to extension hashes. Aborting');
            }

            $this->writeHistoryEntry(
                'Tagging and pushing commit "' . $foundCommitHash . '" of extension "' . $extension . '" with tag "' . $event->tag . '"'
            );
            $this->gitCommand($extensionWorkingCopy, true, 'tag', '-f', $event->tag, $foundCommitHash);
            $this->gitCommand($extensionWorkingCopy, true, 'push', 'origin', $event->tag);

            // Send a heartbeat after an extension has been handled (a single extension should hopefully not take longer than 2 minutes)
            $rabbitIO->read(0);
        }
    }

    /**
     * Used by integration test to restrict testing to a given set of extensions.
     *
     * @param array $extensions
     * @internal
     */
    public function setExtensions(array $extensions): void
    {
        $this->overrideExtensionList = $extensions;
    }

    /**
     * Run a single git command, handle logging and output and break on error.
     *
     * @param GitWorkingCopy $workingCopy The working copy to perform this git command on
     * @param bool $silent True if std output should NOT be logged
     * @param string $command
     * @param mixed ...$arguments
     * @return string
     */
    private function gitCommand(GitWorkingCopy $workingCopy, bool $silent, string $command, ...$arguments)
    {
        $gitWrapper = $workingCopy->getWrapper();
        $gitWrapper->addOutputEventSubscriber($this->gitOutputListener);
        try {
            $standardOutput = $workingCopy->run($command, $arguments);
        } catch (GitException $e) {
            // Log and throw up if command was not successful
            $errorOutput = $this->gitOutputListener->output;
            if (!empty($errorOutput)) {
                $this->writeHistoryEntry('Git command error output: ' . $errorOutput, 'WARNING');
            }
            throw $e;
        }
        $gitWrapper->removeOutputEventSubscriber($this->gitOutputListener);
        $errorOutput = $this->gitOutputListener->output;
        if (!empty($standardOutput) && !$silent) {
            $this->writeHistoryEntry('Git command standard output: ' . $standardOutput);
        }
        if (!empty($errorOutput)) {
            $this->writeHistoryEntry('Git command error output: ' . $errorOutput);
        }
        $this->gitOutputListener->output = '';
        return $standardOutput;
    }

    /**
     * Initial clone of main repository if needed, update and
     * pull of core and checkout of source branch
     *
     * @param GitWorkingCopy $workingCopy
     * @param string $sourceBranch
     * @return string
     */
    private function initialCloneAndCheckout(GitWorkingCopy $workingCopy, string $sourceBranch): string
    {
        $standardOutput = '';
        if (!$workingCopy->isCloned()) {
            $this->writeHistoryEntry('Initial clone of mono repo ' . $this->splitMonoRepo . ' to ' . $this->splitCorePath);

            $gitWrapper = $workingCopy->getWrapper();
            $gitWrapper->addOutputEventSubscriber($this->gitOutputListener);
            try {
                $standardOutput = $workingCopy->cloneRepository($this->splitMonoRepo);
            } catch (GitException $e) {
                // Log and throw up if command was not successful
                $errorOutput = $this->gitOutputListener->output;
                if (!empty($errorOutput)) {
                    $this->writeHistoryEntry('Git command error output: ' . $errorOutput, 'WARNING');
                }
                throw $e;
            }
            $workingCopy->setCloned(true);
            $gitWrapper->removeOutputEventSubscriber($this->gitOutputListener);
            $errorOutput = $this->gitOutputListener->output;
            if (!empty($standardOutput)) {
                $this->writeHistoryEntry('Git command standard output: ' . $standardOutput);
            }
            if (!empty($errorOutput)) {
                $this->writeHistoryEntry('Git command error output: ' . $errorOutput);
            }
            $this->gitOutputListener->output = '';

            $this->gitCommand($workingCopy, false, 'checkout', $sourceBranch);
        } else {
            $this->writeHistoryEntry('Updating clone and checkout of ' . $sourceBranch);
            // First fetch to make sure new branches are there
            $this->gitCommand($workingCopy, false, 'fetch');
            $this->gitCommand($workingCopy, false, 'checkout', $sourceBranch);
            // Pull in upstream changes
            $this->gitCommand($workingCopy, false, 'pull');
        }

        return $standardOutput;
    }

    /**
     * Git fetch and checkout a detached head (a tag in our case)
     *
     * @param GitWorkingCopy $coreWorkingCopy
     * @param string $tag
     */
    private function checkoutDetachedHead(GitWorkingCopy $coreWorkingCopy, string $tag): void
    {
        $this->gitCommand($coreWorkingCopy, false, 'checkout', $tag);
    }

    /**
     * Clone / fetch a single extension repository for tagging.
     *
     * @param string $extension
     * @return GitWorkingCopy
     */
    private function initialExtensionWorkingCopyOrUpdate(string $extension): GitWorkingCopy
    {
        $extensionCheckoutPath = rtrim($this->splitSingleRepoPath, '/') . '/' . $extension;
        $extensionRemoteUrl = $this->splitSingleRepoBase . $extension . '.git';

        // Clone extensions if needed or fetch them. Note this is
        // different in the tagger: The splitter works on additional remotes in
        // main directory, the tagger works on clones of the extensions in own directories.
        $gitWrapper = new GitWrapper();
        $gitWrapper->setEnvVar('HOME', $_ENV['GIT_HOME'] ?? '');
        $gitWrapper->setPrivateKey($_ENV['GIT_SSH_PRIVATE_KEY'] ?? '');
        // Increase timeout to have a chance initial clone runs through
        $gitWrapper->setTimeout(300);
        $workingCopy = $gitWrapper->workingCopy($extensionCheckoutPath);

        if (!$workingCopy->isCloned()) {
            $this->writeHistoryEntry('Initial clone of extension repo ' . $extensionRemoteUrl . ' to ' . $extensionCheckoutPath);

            $gitWrapper->addOutputEventSubscriber($this->gitOutputListener);
            try {
                $standardOutput = $workingCopy->cloneRepository($extensionRemoteUrl);
            } catch (GitException $e) {
                // Log and throw up if command was not successful
                $errorOutput = $this->gitOutputListener->output;
                if (!empty($errorOutput)) {
                    $this->writeHistoryEntry('Git command error output: ' . $errorOutput, 'WARNING');
                }
                throw $e;
            }
            $workingCopy->setCloned(true);
            $gitWrapper->removeOutputEventSubscriber($this->gitOutputListener);
            $errorOutput = $this->gitOutputListener->output;
            if (!empty($standardOutput)) {
                $this->writeHistoryEntry('Git command standard output: ' . $standardOutput);
            }
            if (!empty($errorOutput)) {
                $this->writeHistoryEntry('Git command error output: ' . $errorOutput);
            }
            $this->gitOutputListener->output = '';
        } else {
            $this->writeHistoryEntry('Fetching extension "' . $extension . '"');
            $this->gitCommand($workingCopy, false, 'fetch', '--quiet', '--all');
            $this->gitCommand($workingCopy, false, 'fetch', '--quiet', '--tags');
        }

        return $workingCopy;
    }

    /**
     * Determine list of extensions to split by looking at typo3/sysext/ after checkout of branch.
     *
     * @return array
     */
    private function getExtensions(): array
    {
        if (!empty($this->overrideExtensionList)) {
            return $this->overrideExtensionList;
        }
        $extensionsInTypo3conf = (new Finder())
            ->directories()
            ->in($this->splitCorePath . '/typo3/sysext/')
            ->depth(0)
            ->sortByName();

        /** @var array<SplFileInfo> $extensions */
        $extensions = [];
        foreach ($extensionsInTypo3conf as $extension) {
            $extensions[] = $extension->getBasename();
        }
        return $extensions;
    }

    /**
     * Determine split binary depending on OS
     *
     * @return string
     */
    private function getSplitBinary(): string
    {
        if (PHP_OS === 'Darwin') {
            $splitBinary = 'splitsh-lite-darwin';
        } elseif (PHP_OS === 'Linux') {
            $splitBinary = 'splitsh-lite-linux';
        } else {
            throw new \RuntimeException('Split binary does not support this ' . PHP_OS . ' platform');
        }
        return $splitBinary;
    }

    /**
     * Helper to log stuff with job context.
     * Depends on rabbit message to be set.
     *
     * @param string $message
     * @param string $level
     */
    private function writeHistoryEntry(string $message, string $level = LogLevel::INFO): void
    {
        if (empty($this->event)) {
            throw new \RuntimeException('Helper can only be called if a rabbit message has been set.');
        }
        $type = $this->event->type === 'patch' ? HistoryEntryType::PATCH : HistoryEntryType::TAG;
        $this->entityManager->persist(
            (new HistoryEntry())
                ->setType($type)
                ->setStatus(SplitterStatus::WORK)
                ->setGroupEntry($this->event->jobUuid)
                ->setData(
                    [
                        'type' => $type,
                        'status' => SplitterStatus::WORK,
                        'triggeredBy' => HistoryEntryTrigger::CLI,
                        'job_uuid' => $this->event->jobUuid,
                        'message' => $message,
                        'sourceBranch' => $this->event->sourceBranch,
                        'targetBranch' => $this->event->targetBranch,
                        'tag' => $this->event->tag,
                        'level' => $level
                    ]
                )
        );
        $this->entityManager->flush();
    }
}
