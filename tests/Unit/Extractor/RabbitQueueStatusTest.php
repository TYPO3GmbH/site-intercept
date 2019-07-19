<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Extractor;

use App\Extractor\RabbitQueueStatus;
use PHPUnit\Framework\TestCase;

class RabbitQueueStatusTest extends TestCase
{
    /**
     * @test
     */
    public function constructorExtractsDetails()
    {
        $subject = new RabbitQueueStatus([
            'consumers' => 1,
            'messages' => 2,
        ]);
        $this->assertTrue($subject->isRabbitOnline);
        $this->assertTrue($subject->isWorkerOnline);
        $this->assertSame(2, $subject->numberOfJobs);
    }
}
