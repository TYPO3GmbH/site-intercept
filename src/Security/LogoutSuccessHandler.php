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
use T3G\Bundle\Datahub\Service\UrlService;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    private UrlService $urlService;
    private string $appUrl;

    public function __construct(UrlService $urlService, string $appUrl)
    {
        $this->urlService = $urlService;
        $this->appUrl = $appUrl;
    }

    public function onLogoutSuccess(Request $request): RedirectResponse
    {
        return new RedirectResponse('/oauth/logout?redirect=' . urlencode($this->urlService->getLogoutUrlWithRedirectToApp($this->appUrl)));
    }
}
