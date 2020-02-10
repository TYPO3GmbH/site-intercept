<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Client\SlackClient;
use App\Creator\SlackCoreNightlyBuildMessage;
use App\Entity\DocumentationJar;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Send slack messages
 */
class SlackService
{
    /**
     * @var SlackClient
     */
    private $client;

    /**
     * @var string
     */
    private $hook;

    /**
     * SlackService constructor.
     *
     * @param SlackClient $client
     * @param string $hook
     */
    public function __construct(SlackClient $client, string $hook)
    {
        $this->client = $client;
        $this->hook = $hook;
    }

    /**
     * Send a nightly build status message to slack
     *
     * @param SlackCoreNightlyBuildMessage $message
     * @return ResponseInterface
     */
    public function sendNightlyBuildMessage(SlackCoreNightlyBuildMessage $message): ResponseInterface
    {
        $response = $this->client->post(
            $this->hook,
            [
                RequestOptions::JSON => $message,
            ]
        );
        return $response;
    }

    /**
     * @param DocumentationJar $jar
     * @return ResponseInterface
     */
    public function sendRepositoryDiscoveryMessage(DocumentationJar $jar): ResponseInterface
    {
        $message = [
            'channel' => '#typo3-documentation',
            'username' => 'Intercept',
            'icon_url' => 'https://intercept.typo3.com/build/images/webhookavatars/default.png',
            'attachments' => [
                [
                    'title' => 'New repository on Intercept',
                    'title_link' => 'https://intercept.typo3.com/admin/docs/deployments',
                    'color' => '#4682B4',
                    'text' => 'Repository *' . $jar->getVendor() . '/' . $jar->getName() . '* was discovered.',
                    'fallback' => 'Repository *' . $jar->getVendor() . '/' . $jar->getName() . '* was discovered.',
                    'footer' => 'TYPO3 Intercept',
                    'footer_icon' => 'https://intercept.typo3.com/build/images/webhookavatars/default.png',
                ]
            ],
        ];

        return $this->sendMessage($message);
    }

    /**
     * @param array $parameters
     * @return ResponseInterface
     */
    protected function sendMessage(array $parameters): ResponseInterface
    {
        $response = $this->client->post(
            $this->hook,
            [
                RequestOptions::JSON => $parameters,
            ]
        );
        return $response;
    }
}
