<?php
declare(strict_types = 1);

namespace T3G\Intercept\Tests\Unit\Git;

use GitWrapper\Event\GitOutputEvent;
use PHPUnit\Framework\TestCase;
use T3G\Intercept\Git\GitOutputListener;

class GitOutputListenerTest extends TestCase
{

    public function setUp()
    {
        $this->backupGlobals = true;
    }

    /**
     * @test
     * @return void
     */
    public function outputListenerAddsOutputToGlobalGitOutput()
    {
        $eventProphecy = $this->prophesize(GitOutputEvent::class);
        $eventProphecy->getBuffer()->willReturn('first output', 'second output');

        $gitOutputListener = new GitOutputListener();
        $gitOutputListener->handleOutput($eventProphecy->reveal());
        $gitOutputListener->handleOutput($eventProphecy->reveal());

        self::assertContains('first output', $GLOBALS['gitOutput']);
        self::assertContains('second output', $GLOBALS['gitOutput']);
    }
}
