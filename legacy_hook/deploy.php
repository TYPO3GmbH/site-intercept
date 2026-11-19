<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Deployer;

/*
 * Deployment of the legacy webhook, run by the "Deployment" job in .github/workflows/legacy-hook.yml:
 *
 *   build/tools/deployer/vendor/bin/dep deploy master --file=legacy_hook/deploy.php
 *
 * The scripts are served directly from the Web/services directory of docs.typo3.org, there are no releases. The
 * build (`git archive` of legacy_hook/ with a production Composer install) is copied into that directory with rsync.
 * Files that only exist on the server are kept.
 */

set('build_path', sys_get_temp_dir() . '/deployer-build-intercept-legacy-hook');

host('master')
    ->setHostname('prod.docs.typo3.com')
    ->setRemoteUser('prod.docs.typo3.com')
    ->set('deploy_path', '/srv/vhosts/prod.docs.typo3.com/site/Web/services')
    // Only connect to the server with the pinned host keys, update build/deploy/known_hosts when they change
    ->setSshArguments([
        '-o UserKnownHostsFile=' . \dirname(__DIR__) . '/build/deploy/known_hosts',
        '-o StrictHostKeyChecking=yes',
    ]);

desc('Builds legacy_hook/ of HEAD with its production dependencies');
task('deploy:build', function (): void {
    $buildPath = get('build_path');
    $build = escapeshellarg($buildPath);
    runLocally("rm -rf $build && mkdir -p $build");
    // The tree of the subdirectory is archived, legacy_hook/ itself is export-ignored for the Intercept build.
    // Run from the project root, in a subdirectory git archive only includes the matching paths of the tree.
    runLocally("git archive --format=tar HEAD:legacy_hook | tar -x -C $build", cwd: \dirname(__DIR__));
    runLocally("rm -rf $build/tests $build/phpunit.xml.dist $build/.gitignore $build/deploy.php");
    runLocally('composer install --no-interaction --no-progress --no-dev --optimize-autoloader', cwd: $buildPath, timeout: 900);
});

desc('Copies the build into the Web/services directory');
task('deploy:upload', function (): void {
    upload('{{build_path}}/', '{{deploy_path}}/', ['timeout' => 900]);
});

desc('Deploys the legacy webhook');
task('deploy', [
    'deploy:build',
    'deploy:upload',
]);
