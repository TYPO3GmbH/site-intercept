<?php
declare(strict_types = 1);

namespace T3G\Intercept\Git;

use GitWrapper\Event\GitOutputEvent;
use GitWrapper\Event\GitOutputListenerInterface;

class GitOutputListener implements GitOutputListenerInterface
{

    public function handleOutput(GitOutputEvent $event)
    {
        $GLOBALS['gitOutput'] .= $event->getBuffer();
    }
}