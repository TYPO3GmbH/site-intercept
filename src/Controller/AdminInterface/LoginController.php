<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller\AdminInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handle web admin interface login
 */
class LoginController extends AbstractController
{
    /**
     * @Route("/admin/login", name="admin_login")
     * @return Response
     */
    public function index(): Response
    {
        return $this->redirectToRoute('home');
    }
}
