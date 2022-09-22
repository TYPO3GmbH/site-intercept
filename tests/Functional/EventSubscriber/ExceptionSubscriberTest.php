<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional\EventSubscriber;

use App\EventSubscriber\ExceptionSubscriber;
use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class ExceptionSubscriberTest extends KernelTestCase
{
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->dispatcher = new EventDispatcher();
    }

    public function testExceptionSubscriberIsCalledOnSuspiciousOperationException(): void
    {
        $exceptionSubscriber = new ExceptionSubscriber();
        $this->dispatcher->addSubscriber($exceptionSubscriber);
        $kernel = new Kernel('test', true);
        $kernel->boot();
        $event = new ExceptionEvent($kernel, new Request(), 2, new SuspiciousOperationException());
        $this->dispatcher->dispatch($event, 'kernel.exception');
        $this->assertInstanceOf(SuspiciousOperationException::class, $event->getThrowable());
    }
}
