<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Intercept\Git;

use GitWrapper\Event\GitLoggerListener;
use GitWrapper\GitWrapper;
use T3G\Intercept\LogManager;

/**
 * Class Client
 *
 * @codeCoverageIgnore tested by integration tests only
 */
class Client
{

    /**
     * @var \GitWrapper\GitWorkingCopy
     */
    protected $workingCopy;

    public function __construct(string $branch = 'master')
    {
        $gitOutputListener = new GitOutputListener();
        $client = new GitWrapper();
        $client->setEnvVar('HOME', getenv('GITHOME'));
        $client->setPrivateKey(getenv('PATH_TO_PRIVATE_KEY'));
        $client->addOutputListener($gitOutputListener);
        $client->addLoggerListener($this->getListener());
        $client->git('config --global user.name "TYPO3.com Service"');
        $client->git('config --global user.email noreply@typo3.com');
        $client->git('config --global url."ssh://typo3com_bamboo@review.typo3.org:29418".pushInsteadOf git://git.typo3.org');
        $this->workingCopy = $client->workingCopy(getenv('PATH_TO_CORE_GIT_CHECKOUT'));
        $this->workingCopy
            ->clean('-d', '-f')
            ->reset('--hard')
            ->checkout($branch)
            ->reset('--hard', 'origin/' . $branch)
            ->fetch();
        if (!$this->workingCopy->isUpToDate()) {
            $this->workingCopy->pull();
        }
    }

    private function getListener()
    {
        $logManager = new LogManager();
        $logger = $logManager->getLogger('git');
        return new GitLoggerListener($logger);
    }

    public function commitPatchAsUser(string $patchFile, array $userData, string $commitMessage)
    {
        $this->workingCopy
            ->apply($patchFile)
            ->add('.')
            ->commit(
                [
                    'author' => '"' . $userData['user'] . '<' . $userData['email'] . '>"',
                    'm' => $commitMessage,
                    'verbose' => true
                ]
            );
    }

    public function pushToGerrit()
    {
        $this->workingCopy->push('origin', 'HEAD:refs/for/master');
    }
}
