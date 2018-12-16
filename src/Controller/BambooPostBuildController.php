<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Creator\GerritBuildStatusMessage;
use App\Creator\SlackCoreNightlyBuildMessage;
use App\Extractor\BambooSlackMessage;
use App\Service\BambooService;
use App\Service\GerritService;
use App\Service\SlackService;
use Psr\Log\LoggerInterface;
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
     * @param GerritService $gerritService
     * @param SlackService $slackService
     * @param LoggerInterface $logger
     * @return Response
     */
    public function index(Request $request, BambooService $bambooService, GerritService $gerritService, SlackService $slackService, LoggerInterface $logger): Response
    {
        // temp hack
        $logger->info($request->request->get('payload'));

        $bambooSlack = new BambooSlackMessage($request);
        $buildDetails = $bambooService->getBuildStatus($bambooSlack);

        if ($bambooSlack->isNightlyBuild) {
            // Send message to slack if a nightly build failed
            if (!$buildDetails->success) {
                $message = new SlackCoreNightlyBuildMessage(
                    SlackCoreNightlyBuildMessage::BUILD_FAILED,
                    $buildDetails->buildKey,
                    $buildDetails->projectName,
                    $buildDetails->planName,
                    $buildDetails->buildNumber
                );
                $slackService->sendNightlyBuildMessage($message);
            }
        } elseif (!empty($buildDetails->change) && !empty($buildDetails->patchSet)) {
            // Vote on gerrit if this build has been triggered by a gerrit push
            $message = new GerritBuildStatusMessage($buildDetails);
            $gerritService->voteOnGerrit($buildDetails, $message);
            $logger->info(
                'Voted ' . ($buildDetails->success === true ? '+1' : '-1') . ' on gerrit'
                . ' due to bamboo build "' . $buildDetails->buildKey . '"'
                . ' for change "' . $buildDetails->change . '"'
                . ' with patch set "' . $buildDetails->patchSet . '"',
                [
                    'type' => 'voteGerrit',
                    'change' => $buildDetails->change,
                    'patch' => $buildDetails->patchSet,
                    'bambooResultKey' => $buildDetails->buildKey
                ]
            );
        }

        return Response::create();
    }
}
