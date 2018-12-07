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
use App\Form\BambooTriggerFormType;
use App\Service\BambooService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handle the web admin interface
 */
class AdminInterfaceController extends AbstractController
{
    /**
     * @Route("/admin", name="admin_index")
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return $this->render('home.html.twig');
    }

    /**
     * @Route("/admin/bamboo", name="admin_bamboo")
     * @param Request $request
     * @param BambooService $bambooService
     * @return Response
     */
    public function bamboo(Request $request, BambooService $bambooService): Response
    {
        $form = $this->createForm(BambooTriggerFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            try {
                $bambooData = new GerritToBambooCore(
                    (string)$formData['change'],
                    $formData['set'],
                    $form->getClickedButton()->getName()
                );
                $bambooService->triggerNewCoreBuild($bambooData);
                $this->addFlash('success', 'bar');
            } catch (DoNotCareException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render(
            'bamboo.html.twig',
            [
                'bambooForm' => $form->createView()
            ]
        );
    }
}
