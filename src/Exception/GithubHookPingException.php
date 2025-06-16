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
 * Exception thrown if GitHub sent a PING hook call when first adding the docs webhook.
 */
class GithubHookPingException extends \Exception
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null, private readonly string $repositoryUrl = '')
    {
        parent::__construct($message, $code, $previous);
    }

    public function getRepositoryUrl(): string
    {
        return $this->repositoryUrl;
    }
}
