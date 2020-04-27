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
use App\Extractor\GithubPushEventForCore;
use App\Service\RabbitPublisherService;
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
     * Called by github post merge, this calls a script to update
     * the git sub tree repositories
     *
     * @Route("/split", name="core_git_split")
     * @param Request $request
     * @param RabbitPublisherService $rabbitService
     * @throws \RuntimeException If locking goes wrong
     * @throws \Exception
     * @return Response
     */
    public function index(Request $request, RabbitPublisherService $rabbitService): Response
    {
        try {
            // This throws exceptions if this push event should not trigger splitting, eg. if branches do not match.
            $pushEventInformation = new GithubPushEventForCore(json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR));
            $rabbitService->pushNewCoreSplitJob($pushEventInformation, 'api');
        } catch (DoNotCareException $e) {
            // Hook payload could not be identified as hook that should trigger git split
        }

        return Response::create();
    }
}
