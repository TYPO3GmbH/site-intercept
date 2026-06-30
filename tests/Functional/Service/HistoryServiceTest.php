<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional\Service;

use App\Dto\HistoryEntryDto;
use App\Enum\HistoryEntryTrigger;
use App\Enum\HistoryEntryType;
use App\Service\HistoryService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use T3G\LibTestHelper\Database\DatabasePrimer;

final class HistoryServiceTest extends KernelTestCase
{
    use DatabasePrimer;

    private HistoryService $historyService;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $this->prime();
        $this->historyService = self::getContainer()->get(HistoryService::class);
    }

    public static function writeHistoryDataProvider(): \Generator
    {
        yield [
            'type' => HistoryEntryType::DOCS_RENDERING,
            'status' => 'history_status',
            'triggeredBy' => HistoryEntryTrigger::API,
            'groupEntry' => null,
            'data' => [
                'foo' => 'bar',
                'baz' => 'bencer',
                'answer' => 42,
            ],
            'expectedData' => [
                'type' => HistoryEntryType::DOCS_RENDERING,
                'status' => 'history_status',
                'triggeredBy' => HistoryEntryTrigger::API,
                'foo' => 'bar',
                'baz' => 'bencer',
                'answer' => 42,
            ],
        ];

        yield [
            'type' => HistoryEntryType::DOCS_RENDERING,
            'status' => 'history_status',
            'triggeredBy' => HistoryEntryTrigger::API,
            'groupEntry' => 'Grouped entry',
            'data' => [
                'type' => HistoryEntryType::DOCS_REDIRECT,
                'status' => 'ignored_status',
                'triggeredBy' => 'ignored_trigger',
                'foo' => 'bar',
                'baz' => 'bencer',
                'answer' => 42,
            ],
            'expectedData' => [
                'type' => HistoryEntryType::DOCS_RENDERING,
                'status' => 'history_status',
                'triggeredBy' => HistoryEntryTrigger::API,
                'foo' => 'bar',
                'baz' => 'bencer',
                'answer' => 42,
            ],
        ];
    }

    #[DataProvider('writeHistoryDataProvider')]
    #[Test]
    public function writeHistory(HistoryEntryType $type, string $status, HistoryEntryTrigger $triggeredBy, ?string $groupEntry, array $data, array $expectedData): void
    {
        $historyEntry = $this->historyService->writeHistory(new HistoryEntryDto(
            type: $type,
            status: $status,
            triggeredBy: $triggeredBy,
            groupEntry: $groupEntry,
            data: $data
        ));

        $expectedGroupEntry = $groupEntry ?? 'default';

        $this->assertSame($type, $historyEntry->getType());
        $this->assertSame($status, $historyEntry->getStatus());
        $this->assertSame($expectedGroupEntry, $historyEntry->getGroupEntry());
        $this->assertSame($expectedData, $historyEntry->getData());
    }
}
