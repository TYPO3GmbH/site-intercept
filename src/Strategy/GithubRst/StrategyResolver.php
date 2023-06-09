<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Strategy\GithubRst;

readonly class StrategyResolver
{
    /**
     * @param StrategyInterface[] $strategies
     */
    public function __construct(private iterable $strategies)
    {
    }

    public function resolve(string $type): StrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->match($type)) {
                return $strategy;
            }
        }

        throw new \InvalidArgumentException('Cannot resolve strategy for type ' . $type);
    }
}
