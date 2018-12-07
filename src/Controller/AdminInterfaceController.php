<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

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
     * @return Response
     */
    public function bamboo(Request $request): Response
    {
        return $this->render('bamboo.html.twig');
    }
}
