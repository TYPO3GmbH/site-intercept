<?php

declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Enum;

final class DocsRenderingHistoryStatus
{
    const BLACKLISTED = 'blacklisted';
    const RE_RENDER_NEEDED = 're-render-needed';
    const NO_COMPOSER_JSON = 'noComposerJson';
    const INVALID_COMPOSER_JSON = 'invalidComposerJson';
    const PACKAGE_REGISTERED_WITH_DIFFERENT_REPOSITORY = 'packageRegisteredWithDifferentRepository';
    const NO_RELEVANT_BRANCH_OR_TAG = 'noRelevantBranchOrTag';
    const MISSING_VALUE_IN_COMPOSER_JSON = 'missingValueInComposerJson';
    const CORE_DEPENDENCY_NOT_SET = 'coreDependencyNotSet';
    const INVALID_DOCS = 'invalidDocs';
    const UNSUPPORTED_HOOK = 'unsupportedHook';
    const BRANCH_DELETED = 'branchDeleted';
    const BRANCH_NO_RST_CHANGES = 'branchNoRstChanges';
    const PACKAGE_DELETED = 'packageDeleted';
    const TRIGGERED = 'triggered';
    const GITHUB_PING = 'githubPing';

    public static array $warnings = [
        self::BLACKLISTED,
        self::RE_RENDER_NEEDED,
        self::NO_COMPOSER_JSON,
        self::INVALID_COMPOSER_JSON,
        self::PACKAGE_REGISTERED_WITH_DIFFERENT_REPOSITORY,
        self::NO_RELEVANT_BRANCH_OR_TAG,
        self::MISSING_VALUE_IN_COMPOSER_JSON,
        self::CORE_DEPENDENCY_NOT_SET,
        self::INVALID_DOCS,
        self::UNSUPPORTED_HOOK,
        self::BRANCH_DELETED,
        self::BRANCH_NO_RST_CHANGES,
        self::PACKAGE_DELETED,
    ];

    public static array $success = [
        self::TRIGGERED,
        self::GITHUB_PING,
    ];

    public static array $messages = [
        self::BLACKLISTED => 'Repository has been blacklisted.',
        self::RE_RENDER_NEEDED => 'Re-rendering needed.',
        self::NO_COMPOSER_JSON => 'No composer.json found.',
        self::INVALID_COMPOSER_JSON => 'Invalid composer.json.',
        self::PACKAGE_REGISTERED_WITH_DIFFERENT_REPOSITORY => 'Package registered with different repository.',
        self::NO_RELEVANT_BRANCH_OR_TAG => 'No relevant branch or tag found.',
        self::MISSING_VALUE_IN_COMPOSER_JSON => 'Missing value in composer.json.',
        self::CORE_DEPENDENCY_NOT_SET => 'Dependency to typo3/core not set.',
        self::INVALID_DOCS => 'Invalid documentation.',
        self::UNSUPPORTED_HOOK => 'Unsupported hook format.',
        self::BRANCH_DELETED => 'Branch has been deleted.',
        self::BRANCH_NO_RST_CHANGES => 'Branch has no rst file changes.',
        self::PACKAGE_DELETED => 'Package has been deleted.',
        self::TRIGGERED => 'Rendering triggered.',
        self::GITHUB_PING => 'Github ping received.',
    ];
}
