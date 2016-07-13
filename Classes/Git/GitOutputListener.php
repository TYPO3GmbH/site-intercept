<?php
declare(strict_types = 1);

namespace T3G\Intercept\Git;

use GitWrapper\Event\GitOutputEvent;
use GitWrapper\Event\GitOutputListenerInterface;

class GitOutputListener implements GitOutputListenerInterface
{

    /**
     * Looks ugly, but as gerrit uses stderr to output the link to the review system - even if nothing
     * goes wrong - this is the only way to capture that output for reuse in the comment on the pull request
     *
     * @param \GitWrapper\Event\GitOutputEvent $event
     */
    public function handleOutput(GitOutputEvent $event)
    {
        $GLOBALS['gitOutput'] .= $event->getBuffer();
    }
}