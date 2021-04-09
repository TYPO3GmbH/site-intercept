<?php

declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

use Symfony\Component\HttpFoundation\Request;

class GithubBuildInfo
{
    public string $buildKey = '';
    public string $link = '';
    public bool $success = false;

    public function __construct(Request $request)
    {
        $content = $request->toArray();
        $this->buildKey = $content['data']['id'] ?? '';
        $this->link = $content['data']['link'] ?? '';
        $this->success = $content['data']['success'] ?? false;
    }
}
