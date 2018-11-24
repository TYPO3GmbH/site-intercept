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
use App\Extractor\GithubPushEventForSplit;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Git sub tree split core to single repositories at
 * https://github.com/typo3-cms. Triggered by github hook on
 * https://github.com/TYPO3/TYPO3.CMS.
 */
class GitSubtreeSplitController extends AbstractController
{
    /**
     * @var bool TRUE if lock is acquired
     */
    protected $isLockAcquired;

    /**
     * @var resource Lock file resource
     */
    protected $lockFilePointer;

    /**
     * Called by github post merge, this calls a script to update
     * the git sub tree repositories
     *
     * @Route("/split", name="core_git_split")
     * @param Request $request
     * @param LoggerInterface $logger
     * @throws \RuntimeException If locking goes wrong
     * @return Response
     */
    public function index(Request $request, LoggerInterface $logger): Response
    {
        try {
            $payload = $request->getContent();
            // This throws exceptions if this push event should not trigger splitting,
            // eg. if branches do not match.
            $pushEventInformation = new GithubPushEventForSplit($payload);
            $this->lockFilePointer = fopen('/tmp/core-git-subtree-split.lock', 'w');
            $this->isLockAcquired = flock($this->lockFilePointer, LOCK_EX);
            if ($this->isLockAcquired) {
                $sourceBranch = $pushEventInformation->sourceBranch;
                $targetBranch = $pushEventInformation->targetBranch;

                $execOutput = [];
                $execReturn = 0;
                exec(
                    __DIR__ . '/../../bin/split.sh ' . escapeshellarg($sourceBranch) . ' ' . escapeshellarg($targetBranch) . ' 2>&1',
                    $execOutput,
                    $execReturn
                );

                $logger->info(
                    'github git split'
                    . ' from ' . $sourceBranch
                    . ' to ' . $targetBranch
                    . ' script return ' . $execReturn
                    . ' with script payload:'
                );
                $logger->info(print_r($execOutput, true));

                flock($this->lockFilePointer, LOCK_UN);
                fclose($this->lockFilePointer);
                $this->isLockAcquired = false;
            } else {
                throw new \RuntimeException('Unable to lock.');
            }
        } catch (DoNotCareException $e) {
            // Hook payload could not be identified as hook that
            // should trigger git split
        }

        return Response::create();
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
