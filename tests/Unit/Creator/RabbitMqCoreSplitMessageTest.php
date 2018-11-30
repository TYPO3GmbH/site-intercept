<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Creator;

use App\Creator\RabbitMqCoreSplitMessage;
use PHPUnit\Framework\TestCase;

class RabbitMqCoreSplitMessageTest extends TestCase
{
    /**
     * @test
     */
    public function messageContainsRelevantInformation()
    {
        $message = new RabbitMqCoreSplitMessage('mySource', 'myTarget');
        $this->assertSame('mySource', $message->sourceBranch);
        $this->assertSame('myTarget', $message->targetBranch);
    }

    /**
     * @test
     */
    public function serializedMessageContainsRelevantInformation()
    {
        $message = new RabbitMqCoreSplitMessage('mySource', 'myTarget');
        $jsonMessage = json_encode($message);
        $this->assertRegExp('/mySource/', $jsonMessage);
        $this->assertRegExp('/myTarget/', $jsonMessage);
    }

    /**
     * @test
     */
    public function throwsWithEmptySource()
    {
        $this->expectException(\RuntimeException::class);
        new RabbitMqCoreSplitMessage('', 'myTarget');
    }

    /**
     * @test
     */
    public function throwsWithEmptyTarget()
    {
        $this->expectException(\RuntimeException::class);
        new RabbitMqCoreSplitMessage('mySource', '');
    }
}
