<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

/**
 * Extract gerrit review url from a local git push
 * needed by pull request transformer.
 */
class GitPushOutput
{
    /**
     * @var string Review extracted from a git push output
     */
    public $reviewUrl;

    /**
     * Extract review URL
     *
     * @param string $output
     */
    public function __construct(string $output)
    {
        if (preg_match('/(?<reviewUrl>https\:\/\/review\.typo3\.org\/\d+)/m', $output, $matches) > 0) {
            $this->reviewUrl = $matches['reviewUrl'];
        } else {
            throw new \RuntimeException('No review url found - push went wrong?');
        }
    }
}
