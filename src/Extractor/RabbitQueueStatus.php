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
 * Service class to retrieve various rabbit mq stats and data
 * via rabbit http management api.
 */
class RabbitQueueStatus
{
    /**
     * @var bool True if rabbit mq service is online
     */
    public bool $isRabbitOnline;

    /**
     * @var bool True if the one worker is online
     */
    public bool $isWorkerOnline;

    /**
     * @var int Number of messages in queue
     */
    public int $numberOfJobs;

    /**
     * @param array $body
     */
    public function __construct(array $body)
    {
        $this->isRabbitOnline = !empty($body);
        $this->isWorkerOnline = ($body['consumers'] ?? 0) === 1;
        $this->numberOfJobs = $body['messages'] ?? 0;
    }
}
