<?php
declare(strict_types = 1);

namespace App\Linker;

/*
 * This file is part of the package t3g/intercept-legacy-hook.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

class ResponseDescriber
{
    public function __construct(
        public int $statusCode,
        public array $headers,
        public string $body,
    ) {}
}
