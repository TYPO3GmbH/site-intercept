<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Exception;

/**
 * Exception thrown if a (documentation) package name (vender/name) is already registered
 * with a different repository.
 */
class DocsPackageRegisteredWithDifferentRepositoryException extends \Exception
{
}
