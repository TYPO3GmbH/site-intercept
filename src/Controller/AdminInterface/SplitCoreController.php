<?php

declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller\AdminInterface;

use App\Entity\HistoryEntry;
use App\Enum\SplitterStatus;
use App\Extractor\GithubPushEventForCore;
use App\Form\SplitCoreSplitFormType;
use App\Form\SplitCoreTagFormType;
use App\Repository\HistoryEntryRepository;
use App\Service\RabbitPublisherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handle the web admin interface
 */
class SplitCoreController extends AbstractController
{
    /**
     * @Route("/admin/split/core", name="admin_split_core")
     * @param Request $request
     * @param RabbitPublisherService $rabbitService
     * @param HistoryEntryRepository $historyEntryRepository
     * @throws \Exception
     * @return Response
     */
    public function index(
        Request $request,
        RabbitPublisherService $rabbitService,
        HistoryEntryRepository $historyEntryRepository
    ): Response {
        $splitForm = $this->createForm(SplitCoreSplitFormType::class);
        $tagForm = $this->createForm(SplitCoreTagFormType::class);

        $splitForm->handleRequest($request);
        if ($splitForm instanceof Form && $splitForm->isSubmitted() && $splitForm->isValid()) {
            $this->denyAccessUnlessGranted('ROLE_USER');
            $branch = $splitForm->getClickedButton()->getName();
            $pushEventInformation = new GithubPushEventForCore(['ref' => 'refs/heads/' . $branch]);
            $rabbitService->pushNewCoreSplitJob($pushEventInformation, 'interface');
            $this->addFlash(
                'success',
                'Triggered split job for core branch "' . $pushEventInformation->targetBranch . '"'
            );
        }

        $tagForm->handleRequest($request);
        if ($tagForm->isSubmitted() && $tagForm->isValid()) {
            $this->denyAccessUnlessGranted('ROLE_USER');
            $tag = $tagForm->getData()['tag'];
            $pushEventInformation = new GithubPushEventForCore(['ref' => 'refs/tags/' . $tag, 'created' => true]);
            $rabbitService->pushNewCoreSplitJob($pushEventInformation, 'interface');
            $this->addFlash(
                'success',
                'Triggered tag job with tag "' . $pushEventInformation->tag . '"'
            );
        }

        /** @var HistoryEntry[] $queueLogs */
        $queueLogs = $historyEntryRepository->findSplitLogsByStatus([SplitterStatus::QUEUED]);
        $splitActions = [];
        foreach ($queueLogs as $queueLog) {
            $splitActions[$queueLog->getGroup()] = [
                'queueLog' => $queueLog,
                'finished' => false,
                'detailLogs' => [],
            ];
            /** @var HistoryEntry[] $detailLogs */
            $detailLogs = $historyEntryRepository->findSplitLogsByStatusAndGroup(
                [
                    SplitterStatus::DISPATCH,
                    SplitterStatus::WORK,
                    SplitterStatus::DONE,
                ],
                $queueLog->getGroup(),
                500
            );
            foreach ($detailLogs as $detailLog) {
                $splitActions[$queueLog->getGroup()]['detailLogs'][] = $detailLog;
                if (($detailLog->getData()['status'] ?? '') === SplitterStatus::DONE) {
                    $splitActions[$queueLog->getGroup()]['finished'] = true;
                    $splitActions[$queueLog->getGroup()]['timeTaken'] = $detailLog->getCreatedAt()->diff($queueLog->getCreatedAt());
                }
            }
        }

        return $this->render(
            'split_core/index.html.twig',
            [
                'splitCoreSplit' => $splitForm->createView(),
                'splitCoreTag' => $tagForm->createView(),
                'logs' => $splitActions,
            ]
        );
    }
}
