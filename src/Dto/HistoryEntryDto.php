<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Dto;

use App\Enum\HistoryEntryTrigger;
use App\Enum\HistoryEntryType;

final readonly class HistoryEntryDto
{
    public function __construct(
        public HistoryEntryType $type,
        public string $status,
        public HistoryEntryTrigger $triggeredBy,
        public ?string $groupEntry = null,
        public array $data = [],
    ) {
    }
}
