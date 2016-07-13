<?php
declare(strict_types = 1);

namespace T3G\Intercept\Tests\Integration\Github;

use T3G\Intercept\Github\PatchSaver;

class PatchSaverTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     * @return void
     */
    public function savePatch()
    {
        $diffUrl = 'https://github.com/psychomieze/TYPO3.CMS/pull/1.patch';
        $patchSaver = new PatchSaver();
        $path = $patchSaver->getLocalDiff($diffUrl);
        $diffContent = file_get_contents($path);
        self::assertContains('TestBlub', $diffContent);
        unlink($path);
    }
}
