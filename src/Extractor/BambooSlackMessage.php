<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

use App\Utility\BranchUtility;
use Symfony\Component\HttpFoundation\Request;

/**
 * Parses the slack message format send to us via Bamboo Slack Notification hook
 */
class BambooSlackMessage
{
    /**
     * @var string Project-Plan-BuildNumber, eg. 'CORE-GTC-30244'
     */
    public $buildKey;

    /**
     * @var bool True if plan key is a nightly build
     */
    public $isNightlyBuild;

    /**
     * @var bool True if plan key is a security build
     */
    public $isSecurityBuild;

    /**
     * Extract relevant information from a bamboo created slack message
     *
     * @param Request $request
     * @throws \InvalidArgumentException
     */
    public function __construct(Request $request)
    {
        $payload = $request->request->get('payload');
        if (
            !empty($payload) &&
            preg_match('/bamboo\.typo3\.com\/browse\/(?<buildKey>.*?)\|/', $payload, $matches)
        ) {
            $this->buildKey = $matches['buildKey'];
            $this->isNightlyBuild = BranchUtility::isBambooNightlyBuild($matches['buildKey']);
            $this->isSecurityBuild = BranchUtility::isBambooSecurityBuild($matches['buildKey']);
        } else {
            throw new \InvalidArgumentException('Bamboo slack message could not be parsed.');
        }
    }
}
