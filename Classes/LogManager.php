<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Intercept;

use Monolog\Logger;
use Noodlehaus\Config;

/**
 * Class LogManager
 *
 * Only the log handlers are currently configurable (see: Configuration/logger.dist.json)
 *
 */
class LogManager
{

    /**
     * @param string $name
     * @param \Noodlehaus\Config|null $config
     * @return \Monolog\Logger
     */
    public function getLogger(string $name, Config $config = null)
    {
        $config = $this->getConfig($name, $config);
        $logger = new Logger($name);
        $handlers = $config->get('Handlers');
        foreach ($handlers as $handler) {
            if (isset($handler['arguments']['path'])) {
                $handler['arguments']['path'] = BASEPATH . '/' . $handler['arguments']['path'];
            }
            $logger->pushHandler(new $handler['class'](...array_values($handler['arguments'])));
        }
        return $logger;
    }

    /**
     * @param string $name
     * @param \Noodlehaus\Config $config
     * @return \Noodlehaus\Config
     */
    protected function getConfig(string $name, Config $config = null)
    {
        if ($config === null) {
            $filename = BASEPATH . '/Configuration/' . $name . '.logger.json';
            if (file_exists($filename)) {
                $config = Config::load($filename);
            } else {
                $config = Config::load(BASEPATH . '/Configuration/logger.dist.json');
            }
        }
        return $config;
    }
}
