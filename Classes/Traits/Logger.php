<?php
declare(strict_types = 1);

namespace T3G\Intercept\Traits;

use Psr\Log\LoggerInterface;
use T3G\Intercept\LogManager;

/**
 * Logger Trait
 *
 * Used for setting the logger in a class independent of prior instantiation
 *
 * @package T3G\Intercept\Traits
 */
trait Logger
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function setLogger(LoggerInterface $logger = null)
    {
        if ($logger === null) {
            $logManager = new LogManager();
            $this->logger = $logManager->getLogger(__CLASS__);
        } else {
            $this->logger = $logger;
        }
    }
}