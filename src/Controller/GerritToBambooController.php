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
use App\Extractor\GerritToBambooCore;
use App\Service\BambooService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Called by core gerrit review system (review.typo3.org) as patchset-created hook.
 * Triggers a new bamboo pre-merge master or pre-merge v8 or similar
 * run that applies the given change and patch set and runs tests.
 */
class GerritToBambooController extends AbstractController
{
    /**
     * @Route("/gerrit", name="gerrit_to_bamboo")
     * @param Request $request
     * @param LoggerInterface $logger
     * @param BambooService $bambooService
     * @return Response
     */
    public function index(Request $request, LoggerInterface $logger, BambooService $bambooService): Response
    {
        try {
            $branch = $request->get('branch');
            $bambooData = new GerritToBambooCore(
                $request->get('changeUrl'),
                (int)$request->get('patchset'),
                $branch
            );
            $bambooBuild = $bambooService->triggerNewCoreBuild($bambooData);
            $logger->info(
                'Triggered bamboo core build "' . $bambooBuild->buildResultKey . '"'
                . ' for change "' . $bambooData->changeId . '"'
                . ' with patch set "' . $bambooData->patchSet . '"'
                . ' on branch "' . $branch . '".',
                [
                    'type' => 'triggerBamboo',
                    'change' => $bambooData->changeId,
                    'patch' => $bambooData->patchSet,
                    'branch' => $branch,
                    'bambooKey' => $bambooBuild->buildResultKey
                ]
            );
        } catch (DoNotCareException $e) {
            // Do not care if pushed to some other branch than the
            // ones we do want to handle.
        }
        return Response::create();
    }
}
