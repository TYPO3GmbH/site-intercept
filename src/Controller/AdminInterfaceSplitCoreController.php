<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Extractor\GithubPushEventForCore;
use App\Form\SplitCoreSplitFormType;
use App\Form\SplitCoreTagFormType;
use App\Service\RabbitPublisherService;
use App\Service\RabbitStatusService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handle the web admin interface
 */
class AdminInterfaceSplitCoreController extends AbstractController
{
    /**
     * @Route("/admin/split/core", name="admin_split_core");
     * @param Request $request
     * @param RabbitStatusService $rabbitStatus
     * @param RabbitPublisherService $rabbitService
     * @return Response
     * @throws \Exception
     */
    public function index(Request $request, RabbitStatusService $rabbitStatus, RabbitPublisherService $rabbitService): Response
    {
        $splitForm = $this->createForm(SplitCoreSplitFormType::class);
        $tagForm = $this->createForm(SplitCoreTagFormType::class);

        $splitForm->handleRequest($request);
        if ($splitForm->isSubmitted() && $splitForm->isValid()) {
            $branch = $splitForm->getClickedButton()->getName();
            $pushEventInformation = new GithubPushEventForCore(['ref' => 'refs/heads/' . $branch]);
            $rabbitService->pushNewCoreSplitJob($pushEventInformation, 'api');
            $this->addFlash(
                'success',
                'Triggered split job for core branch "' . $pushEventInformation->targetBranch . '"'
            );
        }

        $tagForm->handleRequest($request);
        if ($tagForm->isSubmitted() && $tagForm->isValid()) {
            $tag = $tagForm->getData()['tag'];
            $pushEventInformation = new GithubPushEventForCore(['ref' => 'refs/tags/' . $tag, 'created' => true]);
            $rabbitService->pushNewCoreSplitJob($pushEventInformation, 'api');
            $this->addFlash(
                'success',
                'Triggered tag job with tag "' . $pushEventInformation->tag . '"'
            );
        }

        return $this->render(
            'splitCore.html.twig',
            [
                'splitCoreSplit' => $splitForm->createView(),
                'splitCoreTag' => $tagForm->createView(),
                'rabbitStatus' => $rabbitStatus->getStatus(),
            ]
        );
    }
}
