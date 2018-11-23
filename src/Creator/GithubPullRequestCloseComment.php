<?php
declare(strict_types = 1);
namespace App\Creator;

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use App\Extractor\GitPushOutput;

/**
 * Comment posted on github for transferred pull requests.
 */
class GithubPullRequestCloseComment
{
    /**
     * Link to contribution guide
     */
    private const CONTRIB_GUIDE = 'https://docs.typo3.org/typo3cms/ContributionWorkflowGuide/';

    /**
     * @var string The created comment
     */
    public $comment;

    /**
     * Create message including link to patch on gerrit
     *
     * @param GitPushOutput $gitPushOutput
     */
    public function __construct(GitPushOutput $gitPushOutput)
    {
        $this->comment = 'Thank you for your contribution to TYPO3.'
            . ' We are using Gerrit Code Review for our contributions and'
            . ' took the liberty to convert your pull request to a review in our review system.'
            . "\n"
            . 'You can find your patch at: ' . $gitPushOutput->reviewUrl
            . "\n"
            . 'For further information on how to contribute have a look at ' . self::CONTRIB_GUIDE;
    }
}
