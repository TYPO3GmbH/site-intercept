<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Client;

use GuzzleHttp\Client;

/**
 * Guzzle client executing calls without base_url set
 */
class GeneralClient extends Client
{
}
