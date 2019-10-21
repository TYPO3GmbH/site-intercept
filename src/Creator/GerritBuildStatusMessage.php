<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Creator;

use App\Extractor\BambooBuildStatus;
use App\Utility\TimeUtility;

/**
 * Create a gerrit build status message from a bamboo build.
 */
class GerritBuildStatusMessage
{
    /**
     * @var string The created message
     */
    public $message;

    /**
     * Create a readable message to be shown on gerrit.
     *
     * @param BambooBuildStatus $buildInformation
     */
    public function __construct(BambooBuildStatus $buildInformation)
    {
        $messageParts[] = 'Completed build in '
            . TimeUtility::convertSecondsToHumanReadable($buildInformation->buildDurationInSeconds)
            . ' on ' . $buildInformation->prettyBuildCompletedTime;
        $messageParts[] = 'Test Summary: ' . $buildInformation->testSummary;
        $messageParts[] = 'Find logs and detail information at ' . $buildInformation->buildUrl;
        $this->message = implode(chr(10), $messageParts);
    }
}
