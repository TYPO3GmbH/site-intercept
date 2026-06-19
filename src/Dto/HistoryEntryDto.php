<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Dto;

final readonly class HistoryEntryDto
{
    public function __construct(
        public string $type,
        public string $status,
        public string $triggeredBy,
        public ?string $groupEntry = null,
        public array $data = [],
    ) {
    }
}
