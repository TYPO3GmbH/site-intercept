<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Exception;

/**
 * Thrown if a request does not contain a valid webhook payload.
 */
class InvalidWebHookPayloadException extends \RuntimeException
{
    public function __construct(string $node = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct('Webhook payload does not contain `' . $node . '`', $code, $previous);
    }
}
