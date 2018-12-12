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
use App\Form\BambooCoreTriggerFormType;
use App\Service\BambooService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handle the web admin interface
 */
class AdminInterfaceBambooCoreController extends AbstractController
{
    /**
     * @Route("/admin/bamboo/core", name="admin_bamboo_core")
     * @param Request $request
     * @param BambooService $bambooService
     * @return Response
     */
    public function index(Request $request, BambooService $bambooService): Response
    {
        $patchForm = $this->createForm(BambooCoreTriggerFormType::class);
        $patchForm->handleRequest($request);
        $this->handlePatchForm($patchForm, $bambooService);

        return $this->render(
            'bambooCore.html.twig',
            [
                'bambooForm' => $patchForm->createView()
            ]
        );
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
            $formData = $form->getData();
            try {
                $bambooData = new GerritToBambooCore(
                    (string)$formData['change'],
                    $formData['set'],
                    $form->getClickedButton()->getName()
                );
                $bambooTriggered = $bambooService->triggerNewCoreBuild($bambooData);
                if (!empty($bambooTriggered->buildResultKey)) {
                    $this->addFlash(
                        'success',
                        'Triggered bamboo build'
                        . ' <a href="https://bamboo.typo3.com/browse/' . $bambooTriggered->buildResultKey . '">' . $bambooTriggered->buildResultKey . '</a>'
                        . ' of change "' . $bambooData->changeId . '"'
                        . ' with patch set "' . $bambooData->patchSet . '"'
                        . ' to plan key "' . $bambooData->bambooProject . '".'
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
