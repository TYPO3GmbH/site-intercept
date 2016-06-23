<?php
declare(strict_types = 1);

namespace T3G\Intercept\Tests\Fixtures;

class TestLogHandler implements \Monolog\Handler\HandlerInterface
{
    /**
     * @var string
     */
    public $path;
    /**
     * @var \Psr\Log\LogLevel
     */
    public $level;

    public function __construct(string $path, $level)
    {

        $this->path = $path;
        $this->level = $level;
    }

    public function isHandling(array $record): bool
    {
        return true;
    }

    public function handle(array $record): bool
    {
        return true;
    }

    public function handleBatch(array $records)
    {
    }

    public function close()
    {
    }
}