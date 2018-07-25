<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Intercept\Github;

use Psr\Http\Message\ResponseInterface;

class IssueInformation
{
    public function transformResponse(ResponseInterface $response) : array
    {
        $responseBody = (string)$response->getBody();
        $fullIssueInformation = json_decode($responseBody, true);
        $issueInformation = [
            'title' => $fullIssueInformation['title'],
            'body' => $fullIssueInformation['body'],
            'url' => $fullIssueInformation['html_url']
        ];
        return $issueInformation;
    }
}
