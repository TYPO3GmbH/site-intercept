<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use T3G\Bundle\Datahub\Service\UrlService;

readonly class LogoutSuccessHandler implements EventSubscriberInterface
{
    public function __construct(
        private UrlService $urlService,
        private string $appUrl
    ) {
    }

    public function onLogout(LogoutEvent $logoutEvent): void
    {
        $logoutEvent->setResponse(new RedirectResponse('/oauth/logout?redirect=' . urlencode($this->urlService->getLogoutUrlWithRedirectToApp($this->appUrl))));
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => ['onLogout', 64]];
    }
}
