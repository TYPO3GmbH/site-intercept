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
use Symfony\Component\Routing\RouterInterface;

/**
 * Send Slack messages.
 */
readonly class SlackService
{
    private const AVATAR_URL = 'https://intercept.typo3.com/build/images/webhookavatars/default.png';
    private const SOURCE_URL = 'https://github.com/TYPO3GmbH/site-intercept/blob/develop/src/Service/SlackService.php';

    /**
     * SlackService constructor.
     */
    public function __construct(
        private ClientInterface $client,
        private DocsService $docsService,
        private RouterInterface $router,
        private string $hook
    ) {
    }

    public function sendRepositoryDiscoveryMessage(DocumentationJar $jar): ResponseInterface
    {
        $repoKey = $jar->getVendor() . '/' . $jar->getName();
        $docsLink = $this->docsService->generateLinkToDocs($jar);
        $deploymentsUrl = $this->router->generate('admin_docs_deployments', [], RouterInterface::ABSOLUTE_URL);
        $webhookDocsUrl = $this->docsService->getDocsServer() . '/permalink/h2document:webhook';

        $message = [
            'channel' => '#typo3-documentation',
            'username' => 'Intercept',
            'icon_url' => self::AVATAR_URL,
            'attachments' => [
                [
                    'title' => 'New extension documentation awaiting approval',
                    'title_link' => $deploymentsUrl,
                    'color' => '#FF8C00',
                    'text' => "Repository *{$repoKey}* was discovered and is awaiting approval by a Documentation Team maintainer.\n\n"
                        . ":clipboard: *What happens next:*\n"
                        . "\u{2022} A documentation maintainer will review and approve the repository\n"
                        . "\u{2022} Once approved, docs will be rendered and deployed to <{$docsLink}|docs.typo3.org>\n"
                        . "\u{2022} This is handled by volunteers \u{2014} please be patient\n\n"
                        . ":hammer_and_wrench: *Maintainers:* <{$deploymentsUrl}|Review pending deployments>\n"
                        . ":information_source: *Extension authors:* <{$webhookDocsUrl}|How does this work?>",
                    'fallback' => "Repository {$repoKey} is awaiting documentation approval at {$deploymentsUrl}",
                    'footer' => sprintf("TYPO3 Intercept \u{00b7} <{%s}|(?)>", self::SOURCE_URL),
                    'footer_icon' => self::AVATAR_URL,
                    'mrkdwn_in' => ['text'],
                ],
            ],
        ];

        try {
            return $this->sendMessage($message);
        } catch (ClientException) {
            // avoid UI exceptions if Slack is not available
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
