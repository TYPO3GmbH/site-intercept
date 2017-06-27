<?php
declare(strict_types = 1);

namespace T3G\Intercept;

use Psr\Log\LoggerInterface;
use T3G\Intercept\Github\PushEvent;
use T3G\Intercept\Traits\Logger;

/**
 * Class GitSubtreeSplitController
 *
 * @codeCoverageIgnore Integration tests only
 * @package T3G\Intercept
 */
class GitSubtreeSplitController
{
    use Logger;

    /**
     * CurlGerritPostRequest constructor.
     *
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->setLogger($logger);
    }

    /**
     * Called by github post merge, this calls a script to update
     * the git sub tree repositories
     *
     * @param string $payload
     */
    public function split(string $payload)
    {
        $pushEventInformation = new PushEvent($payload);

        $sourceBranch = $pushEventInformation->getBranchName();
        $targetBranch = $pushEventInformation->getTargetBranch();

        $execOutput = [];
        $execReturn = 0;
        exec(__DIR__ . '/../bin/split.sh ' . escapeshellarg($sourceBranch) . ' ' . escapeshellarg($targetBranch), $execOutput, $execReturn);

        $this->logger->info(
            'github git split from ' . $sourceBranch . ' to ' . $targetBranch . ' script return ' . $execReturn . ' with script payload ' . print_r($execOutput, true)
        );
    }
}