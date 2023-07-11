<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\EventListener;

use App\Repository\HistoryEntryRepository;

readonly class KernelTerminateListener
{
    public function __construct(
        private HistoryEntryRepository $historyEntryRepository
    ) {
    }

    public function __invoke(): void
    {
        $this->historyEntryRepository->deleteOldEntries();
    }
}
