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
 * Properties passed as variables to Bamboo to render documentation
 */
class DocumentationBuildInformation
{
    /**
     * Path to generated build information file, relative to document root, e.g. builds/1893678543347
     *
     * @var int
     */
    private $filePath;

    /**
     * Constructor
     *
     * @param string $filePath
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }
}
