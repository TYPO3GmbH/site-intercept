<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handle the web admin interface.
 */
class HomeController extends AbstractController
{
    #[Route(path: '/admin', name: 'admin_index')]
    #[Route(path: '/', name: 'admin')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }
}
