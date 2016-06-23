<?php
declare(strict_types = 1);

namespace T3G\Intercept\Traits;

use T3G\Intercept\LogManager;

trait Logger
{
    /**
     * @var \Monolog\Logger
     */
    protected $logger;

    public function setLogger(\Monolog\Logger $logger = null)
    {
        if ($logger === null) {
            $logManager = new LogManager();
            $this->logger = $logManager->getLogger(__CLASS__);
        } else {
            $this->logger = $logger;
        }
    }
}