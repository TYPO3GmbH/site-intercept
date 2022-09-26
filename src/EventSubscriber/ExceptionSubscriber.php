<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @return array<string, string|array<string, int>>
     * @codeCoverageIgnore Implicitly called
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'processException',
        ];
    }

    public function processException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            // don't do anything if it's not the main request
            return;
        }

        $throwable = $event->getThrowable();

        if (
            $throwable instanceof SuspiciousOperationException ||
            $throwable instanceof BadRequestHttpException
        ) {
            $event->setResponse(new JsonResponse([
                'error' => 'Bad Request'
            ], JsonResponse::HTTP_BAD_REQUEST));
        }
    }
}
