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
 * Exception thrown if composer.json's host is unknown to the system.
 */
class UnknownComposerJsonUrlException extends \Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly string $composerJsonUrl = '',
        public readonly string $normalizedHost = '',
    ) {
        parent::__construct($message, $code, $previous);
    }
}
