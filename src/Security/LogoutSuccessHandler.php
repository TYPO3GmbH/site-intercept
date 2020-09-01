<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    private string $appUrl;

    public function __construct(string $appUrl)
    {
        $this->appUrl = $appUrl;
    }

    /**
     * Creates a Response object to send upon a successful logout.
     *
     * @param Request $request
     *
     * @return RedirectResponse never null
     */
    public function onLogoutSuccess(Request $request): RedirectResponse
    {
        /*
         * This is a gatekeeper route
         * @see https://github.com/keycloak/keycloak-documentation/blob/master/securing_apps/topics/oidc/keycloak-gatekeeper.adoc#logout-endpoint
         */
        return new RedirectResponse('/oauth/logout?redirect=' . urlencode('https://login.typo3.com/auth/realms/TYPO3/protocol/openid-connect/logout?redirect_uri=' . urlencode('https://' . $this->appUrl)));
    }
}
