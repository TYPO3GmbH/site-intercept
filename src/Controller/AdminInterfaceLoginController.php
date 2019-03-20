<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Handle web admin interface login
 */
class AdminInterfaceLoginController extends AbstractController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @Route("/admin/login", name="admin_login")
     * @param Request $request
     * @param LoggerInterface $logger
     * @param AuthenticationUtils $authUtils
     * @param TokenStorageInterface $tokenStorage
     * @return Response
     */
    public function index(
        Request $request,
        LoggerInterface $logger,
        AuthenticationUtils $authUtils,
        TokenStorageInterface $tokenStorage
    ): Response {
        $this->logger = $logger;

        if ($tokenStorage->getToken()->getUser() instanceof User) {
            return $this->redirect($this->generateUrl('admin_index'));
        }

        // Get the login error if there is one and create a flash message from it
        $error = $authUtils->getLastAuthenticationError();
        if ($error) {
            $this->addFlash(
                'danger',
                'Login not successful: ' . $error->getMessage()
            );
        }

        return $this->render(
            'login.html.twig',
            [
                'last_username' => $authUtils->getLastUsername(),
            ]
        );
    }
}
