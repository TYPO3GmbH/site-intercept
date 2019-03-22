<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

use App\Exception\DoNotCareException;

/**
 * Extract change id and maybe patch id from a gerrit review url.
 * Used in intercept web interface to trigger bamboo by gerrit review url.
 */
class GerritUrl
{
    /**
     * @var int Resolved change number, eg. 48574
     */
    public $changeId;

    /**
     * @var int The patch set, eg. '5'
     */
    public $patchSet;

    /**
     * Extract data from url
     *
     * @param string $url Something like 'https://review.typo3.org/48574/' or 'https://review.typo3.org/#/c/Packages/TYPO3.CMS/+/48574/11'
     * @throws DoNotCareException
     */
    public function __construct(string $url)
    {
        preg_match('/https:\/\/review\.typo3\.org\/(#\/)?(c\/)?(Packages\/TYPO3\.CMS\/\+\/)?([0-9]*)(\/{0,1})?([0-9]*)/', $url, $matches);
        if (!empty($matches[4])) {
            $this->changeId = (int)$matches[4];
        }
        if (!empty($matches[6])) {
            $this->patchSet = (int)$matches[6];
        }
        if (empty($this->changeId)) {
            throw new DoNotCareException();
        }
    }
}
