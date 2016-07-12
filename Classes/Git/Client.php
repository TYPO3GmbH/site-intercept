<?php
declare(strict_types = 1);

namespace T3G\Intercept\Git;

use GitWrapper\GitWrapper;

class Client
{
    protected $repositoryPath;

    /**
     * @param string $path
     * @internal
     */
    public function setRepositoryPath(string $path)
    {
        $this->repositoryPath = $path;
    }

    public function commitPatchAsUser(string $patchFile, array $userData, string $commitMessage)
    {
        $workingCopy = $this->getCleanWorkingCopy();
        $workingCopy->apply($patchFile);
        $workingCopy->add('.');
        $workingCopy->commit(
            [
                'author' => '"' . $userData['user'] . '<' . $userData['email'] . '>"',
                'm' => $commitMessage
            ]
        );
    }

    private function getCleanWorkingCopy()
    {
        $client = new GitWrapper();
        $workingCopy = $client->workingCopy($this->repositoryPath);
        $workingCopy->fetch();
        if(!$workingCopy->isUpToDate()) {
            $workingCopy->pull();
        }
        $workingCopy->reset(['hard' =>true]);

        return $workingCopy;
    }

}