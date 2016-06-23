<?php
declare(strict_types = 1);

namespace T3G\Intercept;

use Monolog\Logger;
use Noodlehaus\Config;

class LogManager
{

    public function getLogger($name, Config $config = null)
    {
        $config = $config ?: Config::load(BASEPATH . '/Configuration/logger.dist.json');
        $logger = new Logger($name);
        $handlers = $config->get('Handlers');
        foreach ($handlers as $handler) {
            if (isset($handler['arguments']['path'])) {
                $handler['arguments']['path'] = BASEPATH . $handler['arguments']['path'];
            }
            $logger->pushHandler(new $handler['class'](...array_values($handler['arguments'])));
        }
        return $logger;
    }


}