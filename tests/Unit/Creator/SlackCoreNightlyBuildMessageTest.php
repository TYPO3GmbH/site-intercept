<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Creator;

use App\Creator\SlackCoreNightlyBuildMessage;
use PHPUnit\Framework\TestCase;

class SlackCoreNightlyBuildMessageTest extends TestCase
{
    /**
     * @test
     */
    public function constructorThrowsOnInvalidStatus()
    {
        $this->expectException(\RuntimeException::class);
        $message = new SlackCoreNightlyBuildMessage(2, 'CORE-TL-1234', 'Core', 'Testbed lolli', 1234);
    }

    /**
     * @test
     */
    public function jsonContainsFailedColor()
    {
        $message = new SlackCoreNightlyBuildMessage(0, 'CORE-TL-1234', 'Core', 'Testbed lolli', 1234);
        $json = json_encode($message);
        $this->assertRegExp('/"color":"danger"/', $json);
    }

    /**
     * @test
     */
    public function jsonContainsData()
    {
        $message = new SlackCoreNightlyBuildMessage(1, 'CORE-TL-1234', 'Core', 'Testbed lolli', 1234);
        $json = json_encode($message);
        $this->assertRegExp('/"color":"good"/', $json);
        $this->assertRegExp('/CORE-TL-1234/', $json);
    }
}
