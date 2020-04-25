<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

/**
 * Represents information from bamboo json body response if
 * a fresh build has been triggered.
 */
class BambooBuildTriggered
{
    /**
     * @var string Build result key, eg. 'CORE-GTC87-3282'
     */
    public string $buildResultKey;

    /**
     * Extract information from a bamboo build status
     *
     * @param string $payload
     */
    public function __construct(string $payload)
    {
        if ($payload === '') {
            $this->buildResultKey = '';
        } else {
            $response = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            $this->buildResultKey = $response['buildResultKey'] ?? '';
        }
    }
}
