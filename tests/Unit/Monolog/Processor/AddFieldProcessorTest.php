<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Monolog\Processor;

use App\Monolog\Processor\AddFieldProcessor;
use PHPUnit\Framework\TestCase;

class AddFieldProcessorTest extends TestCase
{
    /**
     * @test
     */
    public function addFieldProcessorAddsFields()
    {
        $subject = new AddFieldProcessor(['foo' => 'bar']);
        $expected = [
            'extra' => [
                'foo' => 'bar',
            ],
        ];
        $this->assertSame($expected, $subject->__invoke([]));
    }
}
