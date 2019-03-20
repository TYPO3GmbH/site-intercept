<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Form\LoginFormType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
     * @return Response
     */
    public function index(
        Request $request,
        LoggerInterface $logger,
        AuthenticationUtils $authUtils
    ): Response {
        $this->logger = $logger;

        // get the login error if there is one
        $error = $authUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authUtils->getLastUsername();

        if ($this->get('security.token_storage')->getToken()->getUser() instanceof User) {
            return $this->redirect($this->generateUrl('admin_index'));
        }

        return $this->render(
            'login.html.twig',
            [
                'last_username' => $lastUsername,
                'error' => $error,
            ]
        );
    }
}
