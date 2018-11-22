<?php
declare(strict_types = 1);
namespace App\Controller;

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use App\Extractor\BambooBuildStatus;
use App\Extractor\BambooSlackMessage;
use App\Service\BambooService;
use App\Service\GerritService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Triggered by bamboo if a build finished.
 * Used to vote core pre-merge builds on gerrit and
 * to re-trigger nightly builds if needed.
 */
class BambooPostBuildController extends AbstractController
{
    /**
     * @Route("/bamboo", name="bamboo_build_done")
     * @param Request $request
     * @param BambooService $bambooService
     * @return Response
     */
    public function index(
        Request $request,
        BambooService $bambooService,
        GerritService $gerritService
    ): Response
    {
        $buildKey = (new BambooSlackMessage($request->request->get('payload')))->buildKey;
        $rawBuildDetails = $bambooService->getBuildStatus($buildKey);
        $buildDetails = new BambooBuildStatus((string)$rawBuildDetails->getBody());

        if (!empty($buildDetails->change) && !empty($buildDetails->patchSet)) {
            // Vote on gerrit if this build has been triggered by a gerrit push
            $gerritService->voteOnGerrit($buildDetails);
        }

        return Response::create();
    }
}
