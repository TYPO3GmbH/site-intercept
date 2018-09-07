<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Intercept;

use T3G\Intercept\Bamboo\Client;
use T3G\Intercept\Github\DocumentationRenderingRequest;

class DocumentationRenderingController
{

    /**
     * @var Client
     */
    private $bambooRequests;

    public function __construct(Client $bambooRequests = null)
    {
        $this->bambooRequests = $bambooRequests ?: new Client();
    }

    public function transformGithubWebhookIntoRenderingRequest(string $payload)
    {
        try {
            $renderingRequest = new DocumentationRenderingRequest($payload);
        } catch (DoNotCareException $e) {
            return;
        }

        $this->bambooRequests->triggerDocumentationPlan($renderingRequest);
    }
}
