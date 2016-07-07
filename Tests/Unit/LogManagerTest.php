<?php
declare(strict_types = 1);

namespace T3G\Intercept\Tests\Unit;

use Noodlehaus\Config;
use T3G\Intercept\LogManager;

class LogManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @return void
     */
    public function logManagerUsesConfiguredHandler()
    {
        $path = BASEPATH . '/Tests/Fixtures/TestLoggerConfig.json';
        $config = Config::load($path);
        $logManager = new LogManager();
        $logger = $logManager->getLogger(__CLASS__, $config);
        $handlers = $logger->getHandlers();
        foreach ($handlers as $handler) {
            self::assertSame("WARNING", $handler->level);
            self::assertContains("InterceptTest.log", $handler->path);
        }
    }
}
