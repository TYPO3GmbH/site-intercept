<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\GitWrapper\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symplify\GitWrapper\Event\GitOutputEvent;

/**
 * A listener for git wrapper that captures stderr output, too.
 *
 * @codeCoverageIgnore Can not be tested since GitOutputEvent is final :(
 */
class GitOutputListener implements EventSubscriberInterface
{
    /**
     * @var string Output including stderr
     */
    public string $output = '';

    public static function getSubscribedEvents(): array
    {
        return [
            GitOutputEvent::class => ['handleOutput', 0],
        ];
    }

    /**
     * Looks ugly, but as gerrit uses stderr to output the link to the review system - even if nothing
     * goes wrong - this is the only way to capture that output for reuse in the comment on the pull request
     *
     * @param GitOutputEvent $event
     */
    public function handleOutput(GitOutputEvent $event): void
    {
        if ($event->isError()) {
            $this->output .= $event->getBuffer();
        }
    }
}
