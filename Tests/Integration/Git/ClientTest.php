<?php
declare(strict_types = 1);

namespace T3G\Intercept\Tests\Integration\Git;

use T3G\Intercept\Gerrit\CommitMessageCreator;
use T3G\Intercept\Git\Client;
use T3G\Intercept\Github\PatchSaver;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @return void
     */
    public function downloadApplyAndCommitPatch()
    {
        $diffUrl = 'https://github.com/psychomieze/TYPO3.CMS/pull/1.patch';
        $patchSaver = new PatchSaver();
        $localDiff = $patchSaver->getLocalDiff($diffUrl);

        $commitMessageCreator = new CommitMessageCreator();
        $message = $commitMessageCreator->create('this is a subject', 'this is the body', 3456789);

        $userData = ['user' => 'Ilona Important', 'email' => 'ilona@example.com'];

        $client = new Client();
        $client->setRepositoryPath('/Users/psychomieze/Sites/typo3.cms');
        $client->commitPatchAsUser($localDiff, $userData, $message);
    }
}
