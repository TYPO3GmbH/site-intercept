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
use App\Utility\BranchUtility;

/**
 * Extract information from a gerrit push event hook
 * needed to trigger a bamboo pre-merge build. Throws
 * exceptions if not responsible.
 */
class GerritToBambooCore
{
    /**
     * @var int Resolved change number, eg. 48574
     */
    public int $changeId;

    /**
     * @var int The patch set, eg. '5'
     */
    public int $patchSet;

    /**
     * @var string Core branch, eg. 'TYPO3_8-7' or '9.5'
     */
    public string $branch;

    /**
     * @var string The bamboo project that relates to given core pre-merge branch
     */
    public string $bambooProject;

    /**
     * @var bool True if core security repo is triggered
     */
    public bool $isSecurity;

    /**
     * Extract information needed from a gerrit push event hook
     *
     * @param string $change Something like '48574' or 'https://review.typo3.org/48574/' or 'https://review.typo3.org/#/c/48574/11'
     * @param int $set Patch set, eg 5
     * @param string $branch 'master' or 'TYPO3_8-7' or 'branch8_7' or 'nightly9_5', see utility tests
     * @param string $project Name of gerrit project: 'Packages/TYPO3.CMS' for casual core, 'Teams/Security/TYPO3v4-Core' for core security
     * @throws DoNotCareException
     */
    public function __construct(string $change, int $set, string $branch, string $project)
    {
        if ($change === (string)(int)$change) {
            $this->changeId = (int)$change;
        } elseif (preg_match('/.*\/([\d].*?)/U', $change, $matches)) {
            $this->changeId = (int)$matches[1];
        } else {
            throw new DoNotCareException('Could not determine a changeId from "' . $change . '"');
        }
        $this->patchSet = $set;
        if (empty($this->patchSet)) {
            throw new DoNotCareException('Could not determine a patch set from "' . $set . '"');
        }
        if (empty($project)) {
            throw new DoNotCareException('No gerrit project string like "Packages/TYPO3.CMS" given.');
        }
        $this->branch = BranchUtility::resolveCoreMonoRepoBranch($branch);
        $this->isSecurity = BranchUtility::isSecurityProject($project);
        $this->bambooProject = BranchUtility::resolveBambooProjectKey($branch, $this->isSecurity);
    }
}
