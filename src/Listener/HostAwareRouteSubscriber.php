<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContextAwareInterface;

class HostAwareRouteSubscriber implements EventSubscriberInterface
{
    private RequestContextAwareInterface $router;
    private string $appUrl;

    /**
     * @param RequestContextAwareInterface $router
     * @param string                       $appUrl
     */
    public function __construct(RequestContextAwareInterface $router, string $appUrl)
    {
        $this->router = $router;
        $this->appUrl = $appUrl;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onRequest'];
    }

    /**
     * @param RequestEvent $event
     */
    public function onRequest(RequestEvent $event): void
    {
        $this->router->getContext()->setHost($this->appUrl);
        $this->router->getContext()->setHttpsPort(443);
        $this->router->getContext()->setHttpPort(80);
        $this->router->getContext()->setScheme('https');
    }
}
