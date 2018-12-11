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
 * Extract information from a gerrit push event hook
 * needed to trigger a bamboo pre-merge build. Throws
 * exceptions if not responsible.
 */
class GerritToBambooCore
{
    /**
     * @var array Map gerrit branches to bamboo plan keys
     */
    private $branchToProjectKey = [
        'master' => 'CORE-GTC',
        'TYPO3_8-7' => 'CORE-GTC87',
        'TYPO3_8_7' => 'CORE-GTC87',
        'TYPO3_7-6' => 'CORE-GTC76',
        'TYPO3_7_6' => 'CORE-GTC76',
        'nightlyMaster' => 'CORE-GTN',
        'nightly8_7' => 'CORE-GTN87',
    ];

    /**
     * @var int Resolved change number, eg. 48574
     */
    public $changeId;

    /**
     * @var int The patch set, eg. '5'
     */
    public $patchSet;

    /**
     * @var string The bamboo project that relates to given core pre-merge branch
     */
    public $bambooProject;

    /**
     * Extract information needed from a gerrit push event hook
     *
     * @param string $change Something like '48574' or 'https://review.typo3.org/48574/' or 'https://review.typo3.org/#/c/48574/11'
     * @param int $set Patch set, eg 5
     * @param string $branch 'master' or 'TYPO3_8-7' or ''TYPO3_87' or 'nightly87', see $branchToProjectKey
     * @throws DoNotCareException
     * @throws \RuntimeException
     */
    public function __construct(string $change, int $set, string $branch)
    {
        if ($change === (string)(int)$change) {
            $this->changeId = (int)$change;
        } elseif (preg_match('/.*\/([0-9].*?)/U', $change, $matches)) {
            $this->changeId = (int)$matches[1];
        } else {
            throw new DoNotCareException('Could not determine a changeId from "' . $change . '"');
        }
        $this->patchSet = $set;
        if (empty($this->patchSet)) {
            throw new DoNotCareException('Could not determine a patch set from "' . $set . '"');
        }
        if (!array_key_exists($branch, $this->branchToProjectKey)) {
            throw new DoNotCareException('Did not find bamboo project for branch key "' . $branch . '"');
        }
        $this->bambooProject = $this->branchToProjectKey[$branch];
    }
}
