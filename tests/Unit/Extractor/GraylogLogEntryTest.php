<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Extractor;

use App\Extractor\GraylogLogEntry;
use http\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;

class GraylogLogEntryTest extends TestCase
{
    /**
     * @test
     */
    public function constructorExtractsValues()
    {
        $entry = new GraylogLogEntry([
            'application' => 'intercept',
            'ctxt_type' => 'triggerBamboo',
            'env' => 'prod',
            'level' => 6,
            'message' => 'my message',
            'ctxt_branch' => 'master',
            'ctxt_change' => 12345,
            'ctxt_patch' => 2,
            'ctxt_bambooKey' => 'CORE-GTC-1234',
            'ctxt_vote' => '-1',
        ]);
        $this->assertSame('triggerBamboo', $entry->type);
        $this->assertSame('prod', $entry->env);
        $this->assertSame(6, $entry->level);
        $this->assertSame('my message', $entry->message);
        $this->assertSame('master', $entry->branch);
        $this->assertSame(12345, $entry->change);
        $this->assertSame(2, $entry->patch);
        $this->assertSame('CORE-GTC-1234', $entry->bambooKey);
        $this->assertSame('-1', $entry->vote);
    }

    public function constructorThrowsOnMissingDataDataProvider()
    {
        return [
            'nothing set' => [
                [],
            ],
            'application missing' => [[
                'ctxt_type' => 'triggerBamboo',
                'env' => 'prod',
                'level' => 6,
                'message' => 'my message',
            ]],
            'application wrong' => [[
                'application' => 'not intercept',
                'ctxt_type' => 'triggerBamboo',
                'env' => 'prod',
                'level' => 6,
                'message' => 'my message',
            ]],
            'type missing' => [[
                'application' => 'intercept',
                'env' => 'prod',
                'level' => 6,
                'message' => 'my message',
            ]],
            'env missing' => [[
                'application' => 'intercept',
                'ctxt_type' => 'triggerBamboo',
                'level' => 6,
                'message' => 'my message',
            ]],
            'level missing' => [[
                'application' => 'intercept',
                'ctxt_type' => 'triggerBamboo',
                'env' => 'prod',
                'message' => 'my message',
            ]],
            'message missing' => [[
                'application' => 'intercept',
                'ctxt_type' => 'triggerBamboo',
                'env' => 'prod',
                'level' => 6,
            ]],
        ];
    }

    /**
     * @test
     * @dataProvider constructorThrowsOnMissingDataDataProvider
     */
    public function constructorThrowsOnMissingData(array $input)
    {
        $this->expectException(\RuntimeException::class);
        new GraylogLogEntry($input);
    }
}
