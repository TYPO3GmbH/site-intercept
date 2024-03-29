<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

use App\Exception\DoNotCareException;
use Psr\Http\Message\ResponseInterface;

/**
 * Extract information from a GitHub user data request.
 * Triggered by GitHub api service to retrieve at
 * least username and hopefully email.
 */
readonly class GithubUserData
{
    /**
     * @var string User name or user login name, e.g. 'Christian Kuhn' or 'lolli42'
     */
    public string $user;

    /**
     * @var string User email address if set, e.g. 'lolli@schwarzbu.ch'
     */
    public string $email;

    /**
     * Extract information from a GitHub pull request issue.
     *
     * @param ResponseInterface $response Response of a GitHub user API get
     *
     * @throws DoNotCareException
     */
    public function __construct(ResponseInterface $response)
    {
        $responseBody = (string) $response->getBody();
        $userInformation = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        $this->user = $userInformation['name'] ?? $userInformation['login'] ?? '';
        $this->email = $userInformation['email'] ?? 'noreply@example.com';

        if (empty($this->user)) {
            throw new DoNotCareException('Do not care if user information does not contain minimal data');
        }
    }
}
