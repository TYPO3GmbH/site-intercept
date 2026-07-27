<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class IpAddressService
{
    public function __construct(
        private ClientInterface $generalClient,
        private CacheInterface $ipAddressPoolCache,
    ) {
    }

    public function isIpAddressToBeIgnored(string $ipAddress): bool
    {
        if (IpUtils::isPrivateIp($ipAddress)) {
            return false;
        }

        foreach ([
            [$this, 'getGithubAddressPool'],
            [$this, 'getGitlabAddressPool'],
            [$this, 'gitBitbucketCloudAddressPool'],
        ] as $fetcher) {
            $ipAddressPool = $fetcher();
            if (IpUtils::checkIp($ipAddress, $ipAddressPool)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function getGithubAddressPool(): array
    {
        return $this->ipAddressPoolCache->get('github-hooks-ip-range', function (ItemInterface $item) {
            $item->expiresAfter(3600);

            $response = $this->generalClient->request('GET', 'https://api.github.com/meta');
            $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            return $body['hooks'] ?? [];
        });
    }

    /**
     * @return string[]
     */
    private function getGitlabAddressPool(): array
    {
        // see https://docs.gitlab.com/user/gitlab_com/#ip-range
        return [
            '34.74.90.64/28',
            '34.74.226.0/24',
        ];
    }

    /**
     * @return string[]
     */
    private function gitBitbucketCloudAddressPool(): array
    {
        return $this->ipAddressPoolCache->get('bitbucket-cloud-ip-range', function (ItemInterface $item) {
            $item->expiresAfter(3600);

            $response = $this->generalClient->request('GET', 'https://ip-ranges.atlassian.com/');
            $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $cidrs = [];
            foreach ($body['items'] ?? [] as $networkItem) {
                if (in_array('bitbucket', $networkItem['product'], true)) {
                    $cidrs[] = $networkItem['cidr'];
                }
            }

            return $cidrs;
        });
    }
}
