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
    protected $project = 'Core';
    protected $projectId = 27;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->setLogger($logger);
        $this->client = new \Redmine\Client($this->url, getenv('FORGE_ACCESS_TOKEN'));
    }

    public function createIssue(string $title, string $body, string $url) : \SimpleXMLElement
    {
        $description = $body;
        $description .= "\r\n This issue was automatically created from " . $url;
        return $this->client->issue->create(
            [
                'project_id' => $this->projectId,
                'tracker_id' => 4,
                'subject' => $title,
                'description' => $description,
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