<?php
declare(strict_types = 1);

namespace T3G\Intercept\Tests\Integration\Git;

use T3G\Intercept\Git\Client;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @return void
     */
    public function getCleanWorkingCopyResetsRepository()
    {
        $client = new Client();
        $client->setRepositoryPath('/Users/psychomieze/Sites/typo3.cms');
        $workingCopy = $client->getCleanWorkingCopy();
        self::assertContains('HEAD is now at', $workingCopy->getOutput());
    }
}
