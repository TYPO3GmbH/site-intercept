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
use App\Entity\BambooNightlyBuild;
use App\Entity\DocumentationJar;
use App\Enum\DocumentationStatus;
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
    public function index(
        Request $request,
        BambooService $bambooService,
        GerritService $gerritService,
        SlackService $slackService,
        LoggerInterface $logger
    ): Response {
        $bambooSlack = new BambooSlackMessage($request);
        $buildDetails = $bambooService->getBuildStatus($bambooSlack);

        if ($bambooSlack->isNightlyBuild) {
            // Handle if a nightly build failed
            if (!$buildDetails->success) {
                // See if we have a row for this build key in db
                $bambooNightlyBuildRepository = $this->getDoctrine()->getRepository(BambooNightlyBuild::class);
                $bambooNightlyBuild = $bambooNightlyBuildRepository->findOneBy(['buildKey' => $buildDetails->buildKey]);
                if (!$bambooNightlyBuild) {
                    // First run of this build - re-trigger and store it has been re-triggered once
                    $bambooService->reTriggerFailedBuild($buildDetails->buildKey);
                    $bambooNightlyBuild = new BambooNightlyBuild();
                    $bambooNightlyBuild->setBuildKey($buildDetails->buildKey);
                    $bambooNightlyBuild->setFailedRuns(1);
                    $manager = $this->getDoctrine()->getManager();
                    $manager->persist($bambooNightlyBuild);
                    $manager->flush();
                    $logger->info(
                        'Re-triggered nightly build "' . $buildDetails->buildKey . '" due to test failures.',
                        [
                            'type' => 'rebuildNightly',
                            'bambooKey' => $buildDetails->buildKey,
                            'triggeredBy' => 'api',
                        ]
                    );
                } else {
                    // This build has been re-triggered once or more often already
                    // Send message to slack if a nightly build failed
                    $message = new SlackCoreNightlyBuildMessage(
                        SlackCoreNightlyBuildMessage::BUILD_FAILED,
                        $buildDetails->buildKey,
                        $buildDetails->projectName,
                        $buildDetails->planName,
                        $buildDetails->buildNumber
                    );
                    $slackService->sendNightlyBuildMessage($message);
                    $logger->info(
                        'Reported failing build "' . $buildDetails->buildKey . '" to slack.',
                        [
                            'type' => 'reportBrokenNightly',
                            'bambooKey' => $buildDetails->buildKey,
                            'triggeredBy' => 'api',
                        ]
                    );
                }
            }
        } elseif (!empty($buildDetails->change) && !empty($buildDetails->patchSet)) {
            // Vote on gerrit if this build has been triggered by a gerrit push
            $message = new GerritBuildStatusMessage($buildDetails);
            $gerritService->voteOnGerrit($buildDetails, $message);
            $vote = $buildDetails->success === true ? '+1' : '-1';
            $logger->info(
                'Voted ' . $vote . ' on gerrit'
                . ' due to bamboo build "' . $buildDetails->buildKey . '"'
                . ' for change "' . $buildDetails->change . '"'
                . ' with patch set "' . $buildDetails->patchSet . '"',
                [
                    'type' => 'voteGerrit',
                    'change' => $buildDetails->change,
                    'patch' => $buildDetails->patchSet,
                    'bambooKey' => $buildDetails->buildKey,
                    'isSecurity' => (int)$bambooSlack->isSecurityBuild,
                    'vote' => $vote,
                    'triggeredBy' => 'api',
                ]
            );
        } elseif (strpos($buildDetails->buildKey, 'CORE-DDEL-') === 0) {
            // This is a back-channel triggered by Bamboo after a "documentation deletion" build is done
            $manager = $this->getDoctrine()->getManager();
            $documentationJarRepository = $this->getDoctrine()->getRepository(DocumentationJar::class);

            /** @var DocumentationJar $documentationEntry */
            $documentationEntry = $documentationJarRepository->findOneBy(['buildKey' => $buildDetails->buildKey]);

            if ($buildDetails->success) {
                // Build was successful, delete documentation from database
                $manager->remove($documentationEntry);
                $manager->flush();

                $logger->info(
                    'Deleted documentation'
                    . ' due to bamboo build "' . $buildDetails->buildKey . '"',
                    [
                        'type' => 'docsRendering',
                        'status' => 'documentationDeleted',
                        'triggeredBy' => 'api',
                        'repository' => $documentationEntry->getRepositoryUrl(),
                        'package' => $documentationEntry->getPackageName(),
                        'bambooKey' => $buildDetails->buildKey,
                    ]
                );
            } else {
                // Build failed, revert status of documentation to "rendered"
                $documentationEntry
                    ->setStatus(DocumentationStatus::STATUS_RENDERED)
                    ->setBuildKey('');
                $manager->persist($documentationEntry);
                $manager->flush();

                $logger->warning(
                    'Failed to delete documentation'
                    . ' due to bamboo build "' . $buildDetails->buildKey . '"',
                    [
                        'type' => 'docsRendering',
                        'status' => 'documentationDeleteFailed',
                        'triggeredBy' => 'api',
                        'repository' => $documentationEntry->getRepositoryUrl(),
                        'package' => $documentationEntry->getPackageName(),
                        'bambooKey' => $buildDetails->buildKey,
                    ]
                );
            }
        }

        return Response::create();
    }
}
