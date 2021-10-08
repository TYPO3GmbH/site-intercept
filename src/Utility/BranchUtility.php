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
     * Resolve an incoming string to a valid core mono repo branch name.
     *
     * @param string $identifier
     * @return string
     * @throws DoNotCareException
     */
    public static function resolveCoreMonoRepoBranch(string $identifier): string
    {
        $identifier = mb_strtolower(str_replace(['branch', 'nightly', 'TYPO3_', '_', '-'], ['', '', '', '.', '.'], $identifier));
        if ($identifier !== 'master') {
            $sanityCheck = explode('.', $identifier);
            if (count($sanityCheck) !== 2 || (int)$sanityCheck[0] < 7 || (int)$sanityCheck[1] < 0) {
                throw new DoNotCareException('I do not care');
            }
            $identifier = (int)$sanityCheck[0] . '.' . (int)$sanityCheck[1];
        }
        if ($identifier === '8.7') {
            // Legacy identifier, can be removed once ELTS 8.7 has died
            $identifier = 'TYPO3_8-7';
        }
        if ($identifier === '7.6') {
            // Legacy identifier, can be removed once ELTS 7.6 has died
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
        $splitBranch = str_replace(['TYPO3_', '-'], ['', '.'], $monoRepoBranch);
        if ($splitBranch !== 'master') {
            $sanityCheck = explode('.', $splitBranch);
            if (count($sanityCheck) !== 2 || (int)$sanityCheck[0] < 8 || (int)$sanityCheck[1] < 0) {
                throw new DoNotCareException('I do not care');
            }
            $splitBranch = (int)$sanityCheck[0] . '.' . (int)$sanityCheck[1];
        }
        return $splitBranch;
    }
}
