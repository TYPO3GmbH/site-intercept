<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Dto\HistoryEntryDto;
use App\Entity\HistoryEntry;
use Doctrine\ORM\EntityManagerInterface;

final readonly class HistoryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function writeHistory(HistoryEntryDto $historyEntryDto): HistoryEntry
    {
        $data = $historyEntryDto->data;

        // Unsetting values in incoming data to ensure they are stored properly on top with the upcoming array_merge()
        unset($data['type'], $data['status'], $data['triggeredBy']);

        $data = array_merge([
            'type' => $historyEntryDto->type,
            'status' => $historyEntryDto->status,
            'triggeredBy' => $historyEntryDto->triggeredBy,
        ], $data);

        $historyEntry = (new HistoryEntry())
            ->setType($historyEntryDto->type)
            ->setStatus($historyEntryDto->status)
            ->setData($data);
        if ('' !== (string) $historyEntryDto->groupEntry) {
            $historyEntry->setGroupEntry($historyEntryDto->groupEntry);
        }

        $this->entityManager->persist($historyEntry);
        $this->entityManager->flush();

        return $historyEntry;
    }
}
