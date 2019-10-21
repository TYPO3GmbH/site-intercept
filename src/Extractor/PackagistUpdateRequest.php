<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

use App\Exception\DoNotCareException;

/**
 * Update request for a packagist package
 * created from bitbucket push event payloads
 */
class PackagistUpdateRequest
{
    /**
     * @var string
     */
    private $apiToken;

    /**
     * @var string
     */
    private $repositoryUrl;

    private const PROJECT_MAPPING = [
        'ext-google_ads' => 'https://packagist.org/packages/t3g/google-ads',
    ];
    /**
     * @var string
     */
    private $userName;

    /**
     * Extract data from payload
     *
     * @param array $payload
     * @param string $apiToken
     * @param string $userName
     * @throws DoNotCareException
     */
    public function __construct(array $payload, string $apiToken, string $userName)
    {
        if (!isset($payload['eventKey'], $payload['repository']['name'])) {
            throw new \InvalidArgumentException('Invalid payload, missing \'eventKey\' or \'repository.name\'.', 1557426266);
        }
        if ($payload['eventKey'] !== 'repo:refs_changed') {
            throw new \InvalidArgumentException('Wrong eventKey. Expected \'repo:refs_changed\' got \'' . $payload['eventKey'] . '\'', 1557426270);
        }
        if (self::PROJECT_MAPPING[$payload['repository']['name']] ?? false) {
            $this->repositoryUrl = self::PROJECT_MAPPING[$payload['repository']['name']];
        } else {
            throw new DoNotCareException('Package not known.', 1557420843);
        }
        $this->apiToken = $apiToken;
        $this->userName = $userName;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getApiToken(): string
    {
        return $this->apiToken;
    }

    public function getRepositoryUrl(): string
    {
        return $this->repositoryUrl;
    }
}
