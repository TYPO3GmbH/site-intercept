<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

/**
 * Represents a bamboo status overview. Number of online and busy
 * agents and queue length.
 */
class BambooStatus
{
    /**
     * @var bool True if bamboo is online
     */
    public bool $isOnline;

    /**
     * @var int Number of online agents (agents that have 'enabled' = true set)
     */
    public int $onlineAgents = 0;

    /**
     * @var int Number of busy agents
     */
    public int $busyAgents = 0;

    /**
     * @var int Number of queued jobs waiting to be executed
     */
    public int $queueLength = 0;

    /**
     * Extract information from bamboo agent and queue status
     *
     * @param bool $online True if bamboo is online
     * @param string $agentStatus Result body of agent/remote rest call
     * @param string $queueStatus Result body of queue rest call
     */
    public function __construct(bool $online, string $agentStatus = '', string $queueStatus = '')
    {
        $this->isOnline = $online;
        if ($agentStatus !== '') {
            try {
                $agentStatus = json_decode($agentStatus, true, 512, JSON_THROW_ON_ERROR);
                // We consider agents that have 'enabled'=true as "online"
                $this->onlineAgents = count(array_keys(array_column($agentStatus, 'enabled'), true, true));
                $this->busyAgents = count(array_keys(array_column($agentStatus, 'busy'), true, true));
            } catch (\JsonException $e) {
            }
        }
        if ($queueStatus !== '') {
            try {
                $queueStatus = json_decode($queueStatus, true, 512, JSON_THROW_ON_ERROR);
                $this->queueLength = $queueStatus['queuedBuilds']['size'] ?? 0;
            } catch (\JsonException $e) {
            }
        }
    }
}
