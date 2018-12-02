<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Creator\RabbitMqCoreSplitMessage;
use App\GitWrapper\Event\GitOutputListener;
use GitWrapper\GitException;
use GitWrapper\GitWorkingCopy;
use GitWrapper\GitWrapper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Split mono repo TYPO3.CMS to single repos per extension
 *
 * @codeCoverageIgnore Covered by integration tests
 */
class CoreSplitService
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string Absolute path to core checkout for core split jobs
     */
    private $splitCorePath;

    /**
     * @var string Link to github mono repo, eg. 'git@github.com:TYPO3/TYPO3.CMS.git'
     */
    private $splitMonoRepo;

    /**
     * @var string Base link to github single repos, eg. 'git@github.com:TYPO3-CMS/'
     */
    private $splitSingleRepoBase;

    /**
     * @var GitOutputListener An objects catching git stdout responses
     */
    private $gitOutputListener;

    /**
     * @var array An array filled by integration test to run only a gives series of extensions
     */
    private $overrideExtensionList = [];

    /**
     * @var GitWorkingCopy
     */
    private $workingCopy;

    /**
     * @var RabbitMqCoreSplitMessage Runtime rabbit message
     */
    private $rabbitMessage;

    /**
     * @param LoggerInterface $logger
     * @param string $splitCorePath
     * @param string $splitMonoRepo
     * @param string $splitSingleRepoBase
     * @param GitOutputListener $gitOutputListener
     */
    public function __construct(
        LoggerInterface $logger,
        string $splitCorePath,
        string $splitMonoRepo,
        string $splitSingleRepoBase,
        GitOutputListener $gitOutputListener)
    {
        $this->logger = $logger;
        $this->splitCorePath = $splitCorePath;
        $this->splitMonoRepo = $splitMonoRepo;
        $this->splitSingleRepoBase = $splitSingleRepoBase;
        $this->gitOutputListener = $gitOutputListener;

        $gitWrapper = new GitWrapper();
        $gitWrapper->setEnvVar('HOME', getenv('GIT_HOME'));
        $gitWrapper->setPrivateKey(getenv('GIT_SSH_PRIVATE_KEY'));
        // Increase timeout to have a chance initial clone runs through
        $gitWrapper->setTimeout(300);
        $this->workingCopy = $gitWrapper->workingCopy($splitCorePath);
    }

    /**
     * Execute core splitting
     *
     * @param RabbitMqCoreSplitMessage $rabbitMessage
     */
    public function split(RabbitMqCoreSplitMessage $rabbitMessage): void
    {
        $this->rabbitMessage = $rabbitMessage;
        if (!$this->workingCopy->isCloned()) {
            $this->logInfo('Initial clone of mono repo ' . $this->splitMonoRepo . ' to ' . $this->splitCorePath);
            $this->initialClone();
            $this->gitCommand('checkout', $rabbitMessage->sourceBranch);
        } else {
            $this->logInfo('Updating clone and checkout of ' . $rabbitMessage->sourceBranch);
            // First fetch to make sure new branches are there
            $this->gitCommand('fetch');
            $this->gitCommand('checkout', $rabbitMessage->sourceBranch);
            // Pull in upstream changes
            $this->gitCommand('pull');
        }

        $splitBinary = $this->getSplitBinary();
        $extensions = $this->getExtensions();
        $this->logInfo('Extensions to split: ' . implode(' ', $extensions));

        // Fetch extensions and add remotes if not done, yet
        $existingRemotes = explode("\n", $this->gitCommand('remote'));
        foreach ($extensions as $extension) {
            $fullRemotePath = $this->splitSingleRepoBase . $extension . '.git';
            $this->logInfo('Fetching extension ' . $extension . ' from ' . $fullRemotePath);
            if (!in_array($extension, $existingRemotes)) {
                $this->gitCommand('remote', 'add', $extension, $this->splitSingleRepoBase . $extension . '.git');
            }
            $this->gitCommand('fetch', $extension);
        }

        // Split and push
        foreach ($extensions as $extension) {
            $execOutput = [];
            $execExitCode = 0;
            $command = 'cd ' . escapeshellarg($this->splitCorePath) . ' && '
                . escapeshellcmd('../../bin/' . $splitBinary)
                . ' --prefix=' . escapeshellarg('typo3/sysext/' . $extension)
                . ' --origin=' . escapeshellarg('origin/' . $rabbitMessage->sourceBranch)
                . ' 2>&1';
            $this->logInfo('Splitting extension with command ' . $command);
            $splitSha = exec($command, $execOutput, $execExitCode);
            $this->logInfo('Split operation extension ' . $extension . ' result "' . $execExitCode . '" with sha "' . $splitSha . '" Full output: "' . implode($execOutput) . '"');
            if ($execExitCode !== 0) {
                throw new \RuntimeException('Splitting went wrong. Aborting.');
            }
            $remoteRef = $splitSha . ':refs/heads/' . $rabbitMessage->targetBranch;
            $this->logInfo('Pushing extension ' . $extension . ' to remote ' . $remoteRef);
            $this->gitCommand('push', $extension, $remoteRef);
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
     * @param $command
     * @param mixed ...$arguments
     * @return string
     */
    private function gitCommand($command, ...$arguments)
    {
        $gitWrapper = $this->workingCopy->getWrapper();
        $gitWrapper->addOutputListener($this->gitOutputListener);
        try {
            $standardOutput = $this->workingCopy->run($command, $arguments);
        } catch (GitException $e) {
            // Log and throw up if command was not successful
            $errorOutput = $this->gitOutputListener->output;
            if (!empty($errorOutput)) {
                $this->logInfo('Git command error output: ' . $errorOutput);
            }
            throw $e;
        }
        $gitWrapper->removeOutputListener($this->gitOutputListener);
        $errorOutput = $this->gitOutputListener->output;
        if (!empty($standardOutput)) {
            $this->logInfo('Git command standard output: ' . $standardOutput);
        }
        if (!empty($errorOutput)) {
            $this->logInfo('Git command error output: ' . $errorOutput);
        }
        $this->gitOutputListener->output = '';
        return $standardOutput;
    }

    /**
     * Initial clone of main repository.
     *
     * @return string
     */
    private function initialClone(): string
    {
        $gitWrapper = $this->workingCopy->getWrapper();
        $gitWrapper->addOutputListener($this->gitOutputListener);
        try {
            $standardOutput = $this->workingCopy->cloneRepository($this->splitMonoRepo);
        } catch (GitException $e) {
            // Log and throw up if command was not successful
            $errorOutput = $this->gitOutputListener->output;
            if (!empty($errorOutput)) {
                $this->logInfo('Git command error output: ' . $errorOutput);
            }
            throw $e;
        }
        $this->workingCopy->setCloned(true);
        $gitWrapper->removeOutputListener($this->gitOutputListener);
        $errorOutput = $this->gitOutputListener->output;
        if (!empty($standardOutput)) {
            $this->logInfo('Git command standard output: ' . $standardOutput);
        }
        if (!empty($errorOutput)) {
            $this->logInfo('Git command error output: ' . $errorOutput);
        }
        $this->gitOutputListener->output = '';
        return $standardOutput;
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

        /** @var SplFileInfo $extension */
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
     */
    private function logInfo(string $message): void
    {
        if (empty($this->rabbitMessage)) {
            throw new \RuntimeException('Logger helper can only be called if a rabbit message has been set.');
        }
        $defaultLogContext = ['job_uuid' => $this->rabbitMessage->jobUuid];
        $this->logger->info($message, $defaultLogContext);
    }
}
