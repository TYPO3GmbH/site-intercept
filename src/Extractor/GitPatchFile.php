<?php
declare(strict_types = 1);
namespace App\Extractor;

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

/**
 * Represents a patch file to be applied to local git when
 * transforming github pull requests to gerrit.
 */
class GitPatchFile
{
    /**
     * @var string Absolute path to file
     */
    public $file;

    /**
     * Extract review URL
     *
     * @param string $file
     */
    public function __construct(string $file)
    {
        if (empty($file)) {
            throw new \RuntimeException('Creating patch file went wrong.');
        }
        $this->file = $file;
    }
}
