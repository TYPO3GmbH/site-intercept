<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Deployer;

/*
 * Deployment of Intercept, run by the "Deployment" job in .github/workflows/ci.yml:
 *
 *   build/tools/deployer/vendor/bin/dep deploy <master|develop>
 *
 * The recipe comes from TYPO3GmbH/infra-github-actions (deployer/README.md), checked out in .github/actions/t3g.
 * The legacy webhook in legacy_hook/ is deployed separately, see legacy_hook/deploy.php.
 */

require __DIR__ . '/.github/actions/t3g/deployer/recipe.php';

set('php_version', '8.2');

set('shared_dirs', [
    'var/docs-build-information',
    'var/git-core-pr',
    'var/patches',
]);
set('shared_files', [
    '.env.local',
    'var/data.db',
]);

// The build must not contain the cache written by the Composer scripts, it contains the paths of the build directory
set('build_composer_command', get('build_composer_command') . ' && rm -rf var');

$postReleaseCommands = [
    '{{bin/php}} /usr/bin/composer dump-env prod',
    '{{bin/php}} ./bin/console cache:clear',
    '{{bin/php}} ./bin/console doctrine:migrations:migrate --no-interaction',
];
set('post_release_commands', $postReleaseCommands);

desc('Links the shared docs build information into the public directory');
task('deploy:shared_public', function (): void {
    run('{{bin/symlink}} {{deploy_path}}/shared/var/docs-build-information {{release_path}}/public/docs-build-information');
});
after('deploy:shared', 'deploy:shared_public');

// The host aliases match the branch names, the CI job deploys a branch to the host of the same name
host('master')
    ->setHostname('shared-small.typo3.com')
    ->setRemoteUser('intercept.typo3.com')
    ->set('post_release_commands', [
        ...$postReleaseCommands,
        '{{bin/php}} ./bin/console app:docs-redirect-deploy',
    ]);

host('develop')
    ->setHostname('shared-small.typo3.com')
    ->setRemoteUser('stage.intercept.typo3.com');
