<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Exception;

/**
 * Thrown if a request is not a valid or supported webhook request.
 */
class UnsupportedWebHookRequestException extends \RuntimeException
{
}
