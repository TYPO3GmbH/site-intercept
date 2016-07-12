<?php
declare(strict_types = 1);

namespace T3G\Intercept\Forge;

use Psr\Log\LoggerInterface;
use T3G\Intercept\Traits\Logger;

/**
 * Forge client - handles all interactions with forge
 *
 * @codeCoverageIgnore tested via integration tests only
 * @package T3G\Intercept\Forge
 */
class Client
{
    use Logger;

    protected $client;
    protected $url = 'https://forge.typo3.org';
    protected $accessToken = '2b0f2e95e8f62afa864734191003196f37ff5590';
    protected $project = 'Core';
    protected $projectId = 27;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->setLogger($logger);
        $this->client = new \Redmine\Client($this->url, $this->accessToken);
    }

    public function createIssue(string $title, string $body) : \SimpleXMLElement
    {
        return $this->client->issue->create(
            [
                'project_id' => $this->projectId,
                'tracker_id' => 4,
                'subject' => $title,
                'description' => $body,
                'custom_fields' => [
                    [
                        'id' => 4,
                        'name' => 'TYPO3 Version',
                        'value' => 8,
                    ]
                ]
            ]
        );
    }
}