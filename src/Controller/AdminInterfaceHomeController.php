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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handle the web admin interface
 */
class AdminInterfaceHomeController extends AbstractController
{
    /**
     * @Route("/admin", name="admin_index")
     * @Route("/", name="admin")
     * @return Response
     */
    public function index(): Response
    {
        return $this->render('home.html.twig');
    }
}
