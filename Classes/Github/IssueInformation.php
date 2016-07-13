<?php
declare(strict_types = 1);

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
            'body' => $fullIssueInformation['body']
        ];
        return $issueInformation;
    }
}