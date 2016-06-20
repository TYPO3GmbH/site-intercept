<?php
declare(strict_types = 1);

namespace T3G\Intercept;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LogManager
{

    public static function getLogger($name)
    {
        $logger = new Logger($name);
        $logger->pushHandler(new StreamHandler(__DIR__ . '/Log/Intercept.log', Logger::INFO));
        return $logger;
    }
}