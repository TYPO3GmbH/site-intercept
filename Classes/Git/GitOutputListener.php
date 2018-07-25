<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

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
