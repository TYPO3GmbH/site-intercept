<?php
declare(strict_types = 1);

namespace T3G\Intercept;

use Psr\Log\LoggerInterface;
use T3G\Intercept\Github\PushEvent;
use T3G\Intercept\Traits\Logger;

/**
 * Class GitSubtreeSplitController
 *
 * @codeCoverageIgnore Integration tests only
 * @package T3G\Intercept
 */
class GitSubtreeSplitController
{
    use Logger;

    /**
     * @var bool TRUE if lock is acquired
     */
    protected $isLockAcquired;

    /**
     * @var resource Lock file resource
     */
    protected $lockFilePointer;

    /**
     * CurlGerritPostRequest constructor.
     *
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->setLogger($logger);
    }

    /**
     * Called by github post merge, this calls a script to update
     * the git sub tree repositories
     *
     * @param string $payload
     */
    public function split(string $payload)
    {
        $this->lockFilePointer = fopen('/tmp/core-git-subtree-split.lock', 'w');
        $this->isLockAcquired = flock($this->lockFilePointer, LOCK_EX);
        if ($this->isLockAcquired) {
            $pushEventInformation = new PushEvent($payload);

            $sourceBranch = $pushEventInformation->getBranchName();
            $targetBranch = $pushEventInformation->getTargetBranch();

            $execOutput = [];
            $execReturn = 0;
            exec(__DIR__ . '/../bin/split.sh ' . escapeshellarg($sourceBranch) . ' ' . escapeshellarg($targetBranch) . ' 2>&1', $execOutput, $execReturn);

            $this->logger->info(
                'github git split from ' . $sourceBranch . ' to ' . $targetBranch . ' script return ' . $execReturn . ' with script payload:'
            );
            $this->logger->info(print_r($execOutput, true));
            flock($this->lockFilePointer, LOCK_UN);
            fclose($this->lockFilePointer);
            $this->isLockAcquired = false;
        } else {
            throw new \RuntimeException('Unable to lock.');
        }
    }

    /**
     * Release lock on shutdown
     */
    public function __destruct()
    {
        if ($this->isLockAcquired) {
            flock($this->lockFilePointer, LOCK_UN);
            fclose($this->lockFilePointer);
            $this->isLockAcquired = false;
        }
    }
}