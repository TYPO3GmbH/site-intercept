<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Exception;

use Throwable;

/**
 * Exception thrown if github sent a PING hook call when first adding the docs webhook.
 */
class GithubHookPingException extends \Exception
{
    private string $repositoryUrl;

    public function __construct(string $message = '', int $code = 0, Throwable $previous = null, string $repositoryUrl = '')
    {
        parent::__construct($message, $code, $previous);
        $this->repositoryUrl = $repositoryUrl ?? '';
    }

    public function getRepositoryUrl(): string
    {
        return $this->repositoryUrl;
    }
}
