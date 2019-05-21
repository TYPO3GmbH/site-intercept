<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Cache;

use DateInterval;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DocsAssetsCache
{
    /**
     * @var string
     */
    private $ttl = '24 hours';

    /**
     * @var FilesystemAdapter
     */
    private $cache;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->cache = new TagAwareAdapter(
            new FilesystemAdapter('', 0, $parameterBag->get('kernel.cache_dir') . '/DocsAssetsCache')
        );
    }

    /**
     * @param string $cacheIdentifier
     * @param null $default
     * @return mixed|null
     */
    public function get(string $cacheIdentifier, $default = null)
    {
        $cacheItem = $this->cache->getItem($cacheIdentifier);
        $cacheItem->expiresAfter(DateInterval::createFromDateString($this->ttl));
        return $cacheItem->isHit()
            ? $cacheItem->get()
            : $default;
    }

    /**
     * @param string $cacheIdentifier
     * @param $data
     * @param array $tags
     */
    public function set(string $cacheIdentifier, $data, array $tags = []): void
    {
        $cacheItem = $this->cache->getItem($cacheIdentifier);
        $cacheItem
            ->tag(array_unique($tags))
            ->set($data);
        $this->cache->save($cacheItem);
    }

    /**
     * @param string $cacheIdentifier
     */
    public function delete(string $cacheIdentifier): void
    {
        $this->cache->delete($cacheIdentifier);
    }

    /**
     * @param string $tag
     * @return bool
     */
    public function invalidateByTag(string $tag): bool
    {
        return $this->invalidateByTags([$tag]);
    }

    /**
     * @param array $tags
     * @return bool
     */
    public function invalidateByTags(array $tags): bool
    {
        return $this->cache->invalidateTags($tags);
    }
}
