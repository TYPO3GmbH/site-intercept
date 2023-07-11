<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\DocumentationJar;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Send slack messages.
 */
readonly class SlackService
{
    /**
     * SlackService constructor.
     */
    public function __construct(
        private ClientInterface $client,
        private string $hook
    ) {
    }

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
                ],
            ],
        ];

        try {
            return $this->sendMessage($message);
        } catch (ClientException) {
            // avoid UI exceptions if slack is not available
            return new Response();
        }
    }

    protected function sendMessage(array $parameters): ResponseInterface
    {
        return $this->client->request(
            'POST',
            $this->hook,
            [
                RequestOptions::JSON => $parameters,
            ]
        );
    }
}
