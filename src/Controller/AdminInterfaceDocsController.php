<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Form\BambooDocsFluidVhTriggerFormType;
use App\Service\BambooService;
use App\Service\GraylogService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * "Documentation" menu entry of the web admin interface.
 * Allows to trigger rendering and deployment of documentation repositories
 */
class AdminInterfaceDocsController extends AbstractController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @Route("/admin/docs", name="admin_docs")
     *
     * @param Request $request
     * @param LoggerInterface $logger
     * @param BambooService $bambooService
     * @param GraylogService $graylogService
     * @return Response
     */
    public function index(
        Request $request,
        LoggerInterface $logger,
        BambooService $bambooService,
        GraylogService $graylogService
    ): Response {
        $this->logger = $logger;

        $fluidVhForm = $this->createForm(BambooDocsFluidVhTriggerFormType::class);
        $fluidVhForm->handleRequest($request);
        if ($fluidVhForm->isSubmitted() && $fluidVhForm->isValid()) {
            $bambooTriggered = $bambooService->triggerDocumentationFluidVhPlan();
            if (!empty($bambooTriggered->buildResultKey)) {
                $this->addFlash(
                    'success',
                    'Triggered fluid view helper build'
                    . ' <a href="https://bamboo.typo3.com/browse/' . $bambooTriggered->buildResultKey . '">' . $bambooTriggered->buildResultKey . '</a>'
                    . ' of plan key "CORE-DRF".'
                );
                $this->logger->info(
                    'Triggered fluid view helper build "' . $bambooTriggered->buildResultKey . '".',
                    [
                        'type' => 'triggerBambooDocsFluidVh',
                        'bambooKey' => $bambooTriggered->buildResultKey,
                        'triggeredBy' => 'interface',
                    ]
                );
            } else {
                $this->addFlash(
                    'danger',
                    'Bamboo trigger not successful'
                    . ' of plan key "CORE-DRF".'
                );
            }
        }

        $recentLogsMessages = $graylogService->getRecentBambooDocsTriggers();

        return $this->render(
            'docs.html.twig',
            [
                'fluidVhForm' => $fluidVhForm->createView(),
                'logMessages' => $recentLogsMessages,
            ]
        );
    }
}
