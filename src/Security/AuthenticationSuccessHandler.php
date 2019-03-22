<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Add a flash message and log login after login
 */
class AuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    /**
     * @var FlashBagInterface
     */
    private $flashBag;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param HttpUtils $httpUtils
     * @param array $options
     * @param FlashBagInterface|null $flashBag
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        HttpUtils $httpUtils,
        array $options = [],
        FlashBagInterface $flashBag = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct($httpUtils, $options);
        $this->flashBag = $flashBag;
        $this->logger = $logger;
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $this->logger->info(
            'User login succesful, username: "' . $token->getUsername() . '"',
            [
                'type' => 'loginSuccessful',
            ]
        );
        $this->flashBag->add(
            'success',
            'Successfully logged in.'
        );
        return parent::onAuthenticationSuccess($request, $token);
    }
}
