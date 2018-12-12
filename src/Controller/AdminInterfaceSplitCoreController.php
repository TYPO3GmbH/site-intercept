<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Form\SplitCoreSplitFormType;
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
     * @return Response
     */
    public function index(Request $request, RabbitStatusService $rabbitStatus): Response
    {
        //$form = $this->createForm(SplitCoreSplitFormType::class);

        return $this->render(
            'splitCore.html.twig',
            [
                //'splitCoreSplit' => $form->createView(),
                'rabbitStatus' => $rabbitStatus->getStatus(),
            ]
        );
    }
}
