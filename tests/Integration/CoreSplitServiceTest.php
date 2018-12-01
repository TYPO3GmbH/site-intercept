<?php
declare(strict_types = 1);
namespace App\Tests\Integration;

use App\Creator\RabbitMqCoreSplitMessage;
use App\Service\CoreSplitService;
use PHPUnit\Framework\TestCase;

class BambooPostBuildControllerTest extends TestCase
{
    /**
     * @test
     */
    public function monoRepoIsSplit()
    {
        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer();
        /** @var CoreSplitService $subject */
        $subject = $container->get(CoreSplitService::class);
        $subject->setExtensions(['about', 'backend']);
        $message = new RabbitMqCoreSplitMessage('lolli-1', 'lolli-1');
        $subject->split($message);
        $kernel->shutdown();
        $this->assertTrue(true);
    }
}
