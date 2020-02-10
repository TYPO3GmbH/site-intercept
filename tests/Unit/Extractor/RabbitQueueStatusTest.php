<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

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
