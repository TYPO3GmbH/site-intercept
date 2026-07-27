<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait WebhookSignatureTrait
{
    private function assertValidSignature(Request $request, string $secret): void
    {
        $expectedSignature = $request->headers->get('x-hub-signature-256') ?? '';
        if ('' === $expectedSignature) {
            throw new AccessDeniedHttpException('Missing payload signature header');
        }

        $signature = 'sha256=' . hash_hmac('sha256', (string) $request->getContent(), $secret);
        if (!hash_equals($expectedSignature, $signature)) {
            throw new AccessDeniedHttpException('Content doesn\'t match expected signature "' . $expectedSignature . '"');
        }
    }
}
