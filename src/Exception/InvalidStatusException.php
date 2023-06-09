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
 * Thrown if given HTTP status code is invalid for a redirect.
 */
class InvalidStatusException extends \RuntimeException
{
}
