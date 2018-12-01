<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Creator\RabbitMqCoreSplitMessage;
use App\Exception\DoNotCareException;
use App\Extractor\GithubPushEventForSplit;
use App\Service\RabbitSplitService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Git sub tree split core to single repositories at
 * https://github.com/typo3-cms. Triggered by github hook on
 * https://github.com/TYPO3/TYPO3.CMS.
 *
 * @codeCoverageIgnore
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
     * @throws \RuntimeException If locking goes wrong
     * @return Response
     */
    public function index(Request $request, RabbitSplitService $rabbitService): Response
    {
        try {
            // This throws exceptions if this push event should not trigger splitting,
            // eg. if branches do not match.
            $pushEventInformation = new GithubPushEventForSplit($request);
            $queueMessage = new RabbitMqCoreSplitMessage($pushEventInformation->sourceBranch, $pushEventInformation->targetBranch);
            $rabbitService->pushNewCoreSplitJob($queueMessage);
        } catch (DoNotCareException $e) {
            // Hook payload could not be identified as hook that
            // should trigger git split
        }

        return Response::create();
    }
}
