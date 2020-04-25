<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller\AdminInterface;

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
class LoginController extends AbstractController
{
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
        if ($tokenStorage->getToken()->getUser() instanceof User) {
            // @codeCoverageIgnoreStart
            // Successful login can't be tested directly
            return $this->redirect($this->generateUrl('admin_index'));
            // @codeCoverageIgnoreEnd
        }

        // Get the login error if there is one and create a flash message from it
        $error = $authUtils->getLastAuthenticationError();
        if ($error !== null) {
            $this->addFlash(
                'danger',
                'Login not successful: ' . $error->getMessage()
            );
            $logger->warning(
                'Failed user login, username: "' . $authUtils->getLastUsername() . '"',
                [
                    'type' => 'loginFailed',
                    'username' => $authUtils->getLastUsername(),
                ]
            );
        }

        return $this->render(
            'login/index.html.twig',
            [
                'last_username' => $authUtils->getLastUsername(),
            ]
        );
    }
}
