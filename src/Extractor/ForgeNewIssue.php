<?php
declare(strict_types = 1);
namespace App\Extractor;

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use App\Exception\DoNotCareException;
use Psr\Http\Message\ResponseInterface;

/**
 * Forge issue response object with validation. Created by a
 * forge response object.
 */
class ForgeNewIssue
{
    /**
     * @var int New forge issue is, eg. '12345'
     */
    public $id;

    /**
     * Extract information from a new forge issue response.
     *
     * @param \SimpleXMLElement $forgeIssue A new forge new issue client response
     * @throws \RuntimeException If id is not set in forge issue
     */
    public function __construct(\SimpleXMLElement $forgeIssue)
    {
        $this->id = (int)$forgeIssue->id;

        // Throw if id is not set in forge
        if (empty($this->id)) {
            throw new \RuntimeException();
        }
    }
}
