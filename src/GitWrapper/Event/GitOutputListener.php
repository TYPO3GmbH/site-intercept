<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\GitWrapper\Event;

use GitWrapper\Event\GitOutputEvent;
use GitWrapper\Event\GitOutputListenerInterface;

/**
 * A listener for git wrapper that captures stderr output, too.
 */
class GitOutputListener implements GitOutputListenerInterface
{
    /**
     * @var string Output including stderr
     */
    public $output = '';

    /**
     * Looks ugly, but as gerrit uses stderr to output the link to the review system - even if nothing
     * goes wrong - this is the only way to capture that output for reuse in the comment on the pull request
     *
     * @param \GitWrapper\Event\GitOutputEvent $event
     */
    public function handleOutput(GitOutputEvent $event): void
    {
        $this->output .= $event->getBuffer();
    }
}
