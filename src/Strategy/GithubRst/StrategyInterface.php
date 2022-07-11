<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Strategy\GithubRst;

use App\Extractor\GithubPushEventForCore;

interface StrategyInterface
{
    public function match(string $type): bool;
    public function getFromGithub(GithubPushEventForCore $pushEvent, string $filename): string;
    public function formatResponse(string $response): string;
}
