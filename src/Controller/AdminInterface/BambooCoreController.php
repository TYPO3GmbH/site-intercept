<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller\AdminInterface;

use App\Exception\DoNotCareException;
use App\Extractor\GerritToBambooCore;
use App\Extractor\GerritUrl;
use App\Form\BambooCoreByUrlTriggerFormType;
use App\Form\BambooCoreTriggerFormType;
use App\Form\BambooCoreTriggerFormWithoutPatchSetType;
use App\Service\BambooService;
use App\Service\GerritService;
use App\Service\GraylogService;
use App\Utility\BranchUtility;
use GuzzleHttp\Exception\ServerException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handle the web admin interface
 */
class BambooCoreController extends AbstractController
{
    private LoggerInterface $logger;

    /**
     * @Route("/admin/bamboo/core", name="admin_bamboo_core")
     * @param Request $request
     * @param LoggerInterface $logger
     * @param BambooService $bambooService
     * @param GerritService $gerritService
     * @param GraylogService $graylogService
     * @return Response
     */
    public function index(
        Request $request,
        LoggerInterface $logger,
        BambooService $bambooService,
        GerritService $gerritService,
        GraylogService $graylogService
    ): Response {
        $this->logger = $logger;
        $patchForm = $this->createForm(BambooCoreTriggerFormType::class);
        $patchForm->handleRequest($request);
        $this->handlePatchForm($patchForm, $bambooService);

        $urlForm = $this->createForm(BambooCoreByUrlTriggerFormType::class);
        $urlForm->handleRequest($request);
        $this->handleUrlForm($urlForm, $bambooService, $gerritService);

        $triggerForm = $this->createForm(BambooCoreTriggerFormWithoutPatchSetType::class);
        $triggerForm->handleRequest($request);
        $this->handleTriggerForm($triggerForm, $bambooService);

        $recentLogsMessages = $graylogService->getRecentBambooTriggersAndVotes();

        return $this->render(
            'bamboo_core/index.html.twig',
            [
                'patchForm' => $patchForm->createView(),
                'urlForm' => $urlForm->createView(),
                'triggerForm' => $triggerForm->createView(),
                'logMessages' => $recentLogsMessages,
            ]
        );
    }

    /**
     * Handle from submit for given review url
     *
     * @param FormInterface $form
     * @param BambooService $bambooService
     * @param GerritService $gerritService
     */
    private function handleUrlForm(FormInterface $form, BambooService $bambooService, GerritService $gerritService): void
    {
        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGranted('ROLE_USER');
            $formData = $form->getData();
            try {
                $gerritUrl = new GerritUrl($formData['url']);
                $bambooData = $gerritService->getChangeDetails($gerritUrl->changeId, $gerritUrl->patchSet);
                if ($bambooData->isSecurity) {
                    throw new DoNotCareException('Never trigger security builds from "normal" core build interface');
                }
                $bambooTriggered = $bambooService->triggerNewCoreBuild($bambooData);
                if (!empty($bambooTriggered->buildResultKey)) {
                    $this->addFlash(
                        'success',
                        'Triggered bamboo build <a href="https://bamboo.typo3.com/browse/' . $bambooTriggered->buildResultKey . '" rel="noopener noreferrer" target="_blank">' . $bambooTriggered->buildResultKey . '</a>'
                        . ' of change "' . $bambooData->changeId . '"'
                        . ' with patch set "' . $bambooData->patchSet . '"'
                        . ' to plan key "' . $bambooData->bambooProject . '".'
                    );
                    $this->logger->info(
                        'Triggered bamboo core build "' . $bambooTriggered->buildResultKey . '"'
                        . ' for change "' . $bambooData->changeId . '"'
                        . ' with patch set "' . $bambooData->patchSet . '"'
                        . ' on branch "' . $bambooData->branch . '".',
                        [
                            'type' => 'triggerBamboo',
                            'change' => $bambooData->changeId,
                            'patch' => $bambooData->patchSet,
                            'branch' => $bambooData->branch,
                            'isSecurity' => (int)$bambooData->isSecurity,
                            'bambooKey' => $bambooTriggered->buildResultKey,
                            'triggeredBy' => 'interface',
                        ]
                    );
                } else {
                    $this->addFlash(
                        'danger',
                        'Bamboo trigger not successful with change "' . $bambooData->changeId . '"'
                        . ' and patch set "' . $bambooData->patchSet . '"'
                        . ' to plan key "' . $bambooData->bambooProject . '".'
                    );
                }
            } catch (DoNotCareException $e) {
                $this->addFlash(
                    'danger',
                    'Trigger not successful. Typical cases: invalid url, change or patch set does not exist, invalid branch.'
                );
            } catch (ServerException $e) {
                $this->addFlash(
                    'danger',
                    'Trigger not successful. Gerrit is currently unreachable.'
                );
            }
        }
    }

    /**
     * Handle form submit for change and patch number
     *
     * @param FormInterface $form
     * @param BambooService $bambooService
     */
    private function handlePatchForm(FormInterface $form, BambooService $bambooService): void
    {
        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGranted('ROLE_USER');
            $formData = $form->getData();
            try {
                $bambooData = new GerritToBambooCore(
                    (string)$formData['change'],
                    $formData['set'],
                    $form->getClickedButton()->getName(),
                    'Packages/TYPO3.CMS'
                );
                $bambooTriggered = $bambooService->triggerNewCoreBuild($bambooData);
                if (!empty($bambooTriggered->buildResultKey)) {
                    $this->addFlash(
                        'success',
                        'Triggered bamboo build <a href="https://bamboo.typo3.com/browse/' . $bambooTriggered->buildResultKey . '"rel="noopener noreferrer" target="_blank">' . $bambooTriggered->buildResultKey . '</a>'
                        . ' of change "' . $bambooData->changeId . '"'
                        . ' with patch set "' . $bambooData->patchSet . '"'
                        . ' to plan key "' . $bambooData->bambooProject . '".'
                    );
                    $this->logger->info(
                        'Triggered bamboo core build "' . $bambooTriggered->buildResultKey . '"'
                        . ' for change "' . $bambooData->changeId . '"'
                        . ' with patch set "' . $bambooData->patchSet . '"'
                        . ' on branch "' . $bambooData->branch . '".',
                        [
                            'type' => 'triggerBamboo',
                            'change' => $bambooData->changeId,
                            'patch' => $bambooData->patchSet,
                            'branch' => $bambooData->branch,
                            'isSecurity' => (int)$bambooData->isSecurity,
                            'bambooKey' => $bambooTriggered->buildResultKey,
                            'triggeredBy' => 'interface',
                        ]
                    );
                } else {
                    $this->addFlash(
                        'danger',
                        'Bamboo trigger not successful with change "' . $bambooData->changeId . '"'
                        . ' and patch set "' . $bambooData->patchSet . '"'
                        . ' to plan key "' . $bambooData->bambooProject . '".'
                    );
                }
            } catch (DoNotCareException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }
    }

    /**
     * Handle form submit for simple core branch without applied patch set
     *
     * @param FormInterface $form
     * @param BambooService $bambooService
     * @throws DoNotCareException
     */
    private function handleTriggerForm(FormInterface $form, BambooService $bambooService): void
    {
        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGranted('ROLE_USER');
            $branch = $form->getClickedButton()->getName();
            $bambooProject = BranchUtility::resolveBambooProjectKey($branch, false);
            $bambooTriggered = $bambooService->triggerNewCoreBuildWithoutPatch($bambooProject);
            if (!empty($bambooTriggered->buildResultKey)) {
                $this->addFlash(
                    'success',
                    'Triggered bamboo build <a href="https://bamboo.typo3.com/browse/' . $bambooTriggered->buildResultKey . '" rel="noopener noreferrer" target="_blank">' . $bambooTriggered->buildResultKey . '</a>'
                    . ' to plan key "' . $bambooProject . '".'
                );
                $this->logger->info(
                    'Triggered bamboo core build "' . $bambooTriggered->buildResultKey . '"'
                    . ' on branch "' . $bambooProject . '".',
                    [
                        'type' => 'triggerBamboo',
                        'branch' => $branch,
                        'isSecurity' => false,
                        'bambooKey' => $bambooTriggered->buildResultKey,
                        'triggeredBy' => 'interface',
                    ]
                );
            } else {
                $this->addFlash(
                    'danger',
                    'Bamboo trigger not successful to plan key "' . $bambooProject . '".'
                );
            }
        }
    }
}
