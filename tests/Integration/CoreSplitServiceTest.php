<?php
declare(strict_types = 1);
namespace App\Tests\Integration;

use App\Extractor\GithubPushEventForCore;
use App\Service\CoreSplitService;
use PHPUnit\Framework\TestCase;

class CoreSplitServiceTest extends TestCase
{
    /**
     * How to test:
     *
     * Prepare:
     * * fork core to a local github user, eg. https://github.com/lolli42/TYPO3.CMS
     * * put that URL into .env.test as "CORE_SPLIT_MAIN_REPO"
     * * fork extension "about" and "backend" from https://github.com/typo3-cms/ to your local user
     * * put the base URL of the github user into .env.test as "CORE_SPLIT_SINGLE_REPOS_BASE"
     * * add user "TYPO3 GmbH" as contributor to your "about" and "backend" fork, or use a different user
     *   if your .env GIT_SSH_PRIVATE_KEY uses a different user
     *
     * Prepare patches and tags:
     * * clone your core fork, then
     *   * checkout new branch 'test-1' from master , 'git co -b test-1 origin/master'
     *      * commit a patch to 'about' extension, 'git commit -a -m "patch 1 to about test-1"'
     *      * push that branch, 'git push origin test-1'
     *      * tag as version 'v42.0.0', 'git tag v42.0.0'
     *      * push tag, 'git push --tags'
     *   * checkout new branch 'test-2' from TYPO3_8-7, 'git co -b test-2 origin/TYPO3_8-7'
     *      * commit a patch to 'about' extension, 'git commit -a -m "patch 1 to about test-2"'
     *      * commit a patch to 'backend' extension, 'git commit -a -m "patch 1 to backend test-2"'
     *      * push that branch, 'git push origin test-2'
     *      * tag as version 'v42.1.0', 'git tag v42.1.0'
     *      * push tag, 'git push --tags'
     *      * commit a patch to 'about' extension, 'git commit -a -m "patch 2 to about test-2"'
     *      * commit a patch to 'about' extension, 'git commit -a -m "patch 3 to about test-2"'
     *      * push that branch, 'git push origin test-2'
     *      * tag as version 'v42.1.1', 'git tag v42.1.1'
     *      * push tag, 'git push --tags'
     *      * commit a patch to 'about' extension, 'git commit -a -m "patch 4 to about test-2"'
     *      * push that branch, 'git push origin test-2'
     *
     * Run test:
     * * ./bin/phpunit -c build/phpunit.xml tests/Integration/CoreSplitServiceTest.php
     *
     * Verify:
     * * clone 'about' and 'backend'
     * * checkout 'about' branch test-1, git log:
     *   * "patch 1 to about test-1" is tagged as v42.0.0
     * * checkout 'about' branch test-2, git log:
     *   * "patch 1 to about test-2" is tagged as v42.1.0
     *   * "patch 2 to about test-2" is not tagged
     *   * "patch 3 to about test-2" is tagged as v42.1.1
     *   * "patch 4 to about test-2" is not tagged
     * * checkout 'backend' branch test-2, git log:
     *   * "patch 1 to backend test-2" is tagged as v42.1.0 AND v42.1.1
     */

    /**
     * @test
     */
    public function monoRepoIsSplit()
    {
        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer();
        /** @var CoreSplitService $subject */
        $subject = $container->get(CoreSplitService::class);
        $subject->setExtensions(['about', 'backend']);

        // Split all patches for test-1 branch
        $message = new GithubPushEventForCore();
        $message->sourceBranch = 'test-1';
        $message->targetBranch = 'test-1';
        $message->jobUuid = 'my-uuid-1';
        $message->type = 'patch';
        $subject->split($message);

        // Split all patches for test-2 branch
        $message = new GithubPushEventForCore();
        $message->sourceBranch = 'test-2';
        $message->targetBranch = 'test-2';
        $message->jobUuid = 'my-uuid-2';
        $message->type = 'patch';
        $subject->split($message);

        // Tag v42.0.0
        $message = new GithubPushEventForCore();
        $message->sourceBranch = 'test-1';
        $message->targetBranch = 'test-1';
        $message->jobUuid = 'my-uuid-3';
        $message->type = 'tag';
        $message->tag = 'v42.0.0';
        $subject->tag($message);

        // Tag v42.1.0
        $message = new GithubPushEventForCore();
        $message->sourceBranch = 'test-2';
        $message->targetBranch = 'test-2';
        $message->jobUuid = 'my-uuid-4';
        $message->type = 'tag';
        $message->tag = 'v42.1.0';
        $subject->tag($message);

        // Tag v42.1.1
        $message = new GithubPushEventForCore();
        $message->sourceBranch = 'test-2';
        $message->targetBranch = 'test-2';
        $message->jobUuid = 'my-uuid-5';
        $message->type = 'tag';
        $message->tag = 'v42.1.1';
        $subject->tag($message);

        $kernel->shutdown();
        $this->assertTrue(true);
    }
}
