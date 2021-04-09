<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Extractor\GithubPushEventForCore;
use PhpAmqpLib\Wire\IO\AbstractIO;

/**
 * @codeCoverageIgnore
 */
interface CoreSplitServiceInterface
{
    public function split(GithubPushEventForCore $event, AbstractIO $rabbitIO): void;
    public function tag(GithubPushEventForCore $event, AbstractIO $rabbitIO): void;
}
