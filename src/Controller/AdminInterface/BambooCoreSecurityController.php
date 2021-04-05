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
use App\Form\BambooCoreSecurityByUrlTriggerFormType;
use App\Form\BambooCoreSecurityTriggerFormType;
use App\Service\BambooService;
use App\Service\GerritService;
use App\Service\GraylogService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handle the web admin interface bamboo core security
 */
class BambooCoreSecurityController extends AbstractController
{
    private LoggerInterface $logger;

    /**
     * @Route("/admin/bamboo/core/security", name="admin_bamboo_core_security")
     * @IsGranted("ROLE_ADMIN")
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

        $patchForm = $this->createForm(BambooCoreSecurityTriggerFormType::class);
        $patchForm->handleRequest($request);
        $this->handlePatchForm($patchForm, $bambooService);

        $urlForm = $this->createForm(BambooCoreSecurityByUrlTriggerFormType::class);
        $urlForm->handleRequest($request);
        $this->handleUrlForm($urlForm, $bambooService, $gerritService);

        $recentLogsMessages = $graylogService->getRecentBambooCoreSecurityTriggersAndVotes();

        return $this->render(
            'bamboo_core_security/index.html.twig',
            [
                'patchForm' => $patchForm->createView(),
                'urlForm' => $urlForm->createView(),
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
            $formData = $form->getData();
            try {
                $gerritUrl = new GerritUrl($formData['url']);
                $bambooData = $gerritService->getChangeDetails($gerritUrl->changeId, $gerritUrl->patchSet);
                if (!$bambooData->isSecurity) {
                    throw new DoNotCareException('Only trigger security builds from core security build interface');
                }
                $bambooTriggered = $bambooService->triggerNewCoreBuild($bambooData);
                if (!empty($bambooTriggered->buildResultKey)) {
                    $this->addFlash(
                        'success',
                        'Triggered bamboo build "' . $bambooTriggered->buildResultKey . '"'
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
        if ($form instanceof Form && $form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            try {
                $bambooData = new GerritToBambooCore(
                    (string)$formData['change'],
                    $formData['set'],
                    $form->getClickedButton()->getName(),
                    'Teams/Security/TYPO3v4-Core'
                );
                $bambooTriggered = $bambooService->triggerNewCoreBuild($bambooData);
                if (!empty($bambooTriggered->buildResultKey)) {
                    $this->addFlash(
                        'success',
                        'Triggered bamboo security build ' . $bambooTriggered->buildResultKey . '"'
                        . ' of change "' . $bambooData->changeId . '"'
                        . ' with patch set "' . $bambooData->patchSet . '"'
                        . ' to plan key "' . $bambooData->bambooProject . '".'
                    );
                    $this->logger->info(
                        'Triggered bamboo core security build "' . $bambooTriggered->buildResultKey . '"'
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
}
