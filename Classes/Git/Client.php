<?php
declare(strict_types = 1);

namespace T3G\Intercept\Git;

use GitWrapper\Event\GitLoggerListener;
use GitWrapper\GitWrapper;
use T3G\Intercept\LogManager;

/**
 * Class Client
 *
 * @codeCoverageIgnore tested by integration tests only
 * @package T3G\Intercept\Git
 */
class Client
{

    /**
     * @var \GitWrapper\GitWorkingCopy
     */
    protected $workingCopy;

    public function __construct()
    {
        $gitOutputListener = new GitOutputListener();
        $client = new GitWrapper();
        $client->addLoggerListener($this->getListener());
        $client->addOutputListener($gitOutputListener);
        $client->setPrivateKey(getenv('PATH_TO_PRIVATE_KEY'));
        $this->workingCopy = $client->workingCopy(getenv('PATH_TO_CORE_GIT_CHECKOUT'));
        $this->workingCopy
            ->clean('-d', '-f')
            ->reset('--hard', 'origin/master')
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