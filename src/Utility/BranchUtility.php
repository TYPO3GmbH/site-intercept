<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Utility;

use App\Exception\DoNotCareException;

/**
 * Helper class to convert between branch names
 */
class BranchUtility
{
    /**
     * @var array Identifiers to bamboo project keys mapping. Extend this for new plans.
     */
    private static $branchToProjectKeys = [
        'master' => 'CORE-GTC',
        'nightlyMaster' => 'CORE-GTN',
        '9.5' => 'CORE-GTC95',
        'nightly9.5' => 'CORE-GTN95',
        '8.7' => 'CORE-GTC87',
        'nightly8.7' => 'CORE-GTN87',
        '7.6' => 'CORE-GTC76',
    ];

    /**
     * Have an identifier like 'master', 'masterNightly', 'branch9_5', '9.5'
     * and get the target bamboo plan key. Incoming identifiers are
     * branch names from git, or button values from the web interface.
     *
     * @param string $identifier
     * @return string
     * @throws DoNotCareException
     */
    public static function resolveBambooProjectKey(string $identifier): string
    {
        $identifier = str_replace('branch', '', $identifier);
        $identifier = str_replace('TYPO3_', '', $identifier);
        $identifier = str_replace('_', '.', $identifier);
        $identifier = str_replace('-', '.', $identifier);
        if (!array_key_exists($identifier, static::$branchToProjectKeys)) {
            throw new DoNotCareException('Did not find bamboo project for key "' . $identifier . '"');
        }
        return static::$branchToProjectKeys[$identifier];
    }

    /**
     * Resolve an incoming string to a valid core mono repo branch name.
     *
     * @param string $identifier
     * @return string
     * @throws DoNotCareException
     */
    public static function resolveCoreMonoRepoBranch(string $identifier): string
    {
        $identifier = str_replace('branch', '', $identifier);
        $identifier = str_replace('TYPO3_', '', $identifier);
        $identifier = str_replace('_', '.', $identifier);
        $identifier = str_replace('-', '.', $identifier);
        if ($identifier !== 'master') {
            $sanityCheck = explode('.', $identifier);
            if (count($sanityCheck) !== 2 || (int)$sanityCheck[0] < 7 || (int)$sanityCheck < 0) {
                throw new DoNotCareException();
            }
            $identifier = (int)$sanityCheck[0] . '.' . (int)$sanityCheck[1];
        }
        if ($identifier === '8.7') {
            $identifier = 'TYPO3_8-7';
        }
        if ($identifier === '7.6') {
            $identifier = 'TYPO3_7-6';
        }
        return $identifier;
    }

    /**
     * Translate a given core mono repo branch to a target branch in split repositories.
     *
     * @param string $monoRepoBranch A valid mono repo branch name, eg '9.5', 'master', 'TYPO3_8-7'
     * @return string
     * @throws DoNotCareException
     */
    public static function resolveCoreSplitBranch(string $monoRepoBranch): string
    {
        $splitBranch = str_replace('TYPO3_', '', $monoRepoBranch);
        $splitBranch = str_replace('-', '.', $splitBranch);
        if ($splitBranch !== 'master') {
            $sanityCheck = explode('.', $splitBranch);
            if (count($sanityCheck) !== 2 || (int)$sanityCheck[0] < 8 || (int)$sanityCheck < 0) {
                throw new DoNotCareException();
            }
            $splitBranch = (int)$sanityCheck[0] . '.' . (int)$sanityCheck[1];
        }
        return $splitBranch;
    }

    /**
     * True if given bamboo plan key name is a core nightly build
     *
     * @param string $incomingPlanKey
     * @return bool
     */
    public static function isBambooNightlyBuild(string $incomingPlanKey): bool
    {
        foreach (static::$branchToProjectKeys as $branch => $planKey) {
            if (strpos($incomingPlanKey, $planKey . '-') === 0 && strpos($branch, 'nightly') === 0) {
                return true;
            }
        }
        return false;
    }
}
