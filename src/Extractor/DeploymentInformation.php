<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

/**
 * Class DeploymentInformation
 */
class DeploymentInformation
{
    /**
     * @var string
     */
    private $vendor;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $branch;

    /**
     * @var string
     */
    private $typeLong;

    /**
     * @var string
     */
    private $typeShort;

    /**
     * Constructor
     *
     * @param string $vendor
     * @param string $name
     * @param string $branch
     * @param string $typeLong
     * @param string $typeShort
     */
    public function __construct(string $vendor, string $name, string $branch, string $typeLong, string $typeShort)
    {
        $this->vendor = $vendor;
        $this->name = $name;
        $this->branch = $branch;
        $this->typeLong = $typeLong;
        $this->typeShort = $typeShort;
    }

    /**
     * @return string|null
     */
    public function getVendor(): string
    {
        return $this->vendor;
    }

    /**
     * @return string|null
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPackageName(): string
    {
        return $this->vendor . '/' . $this->name;
    }

    /**
     * @return string|null
     */
    public function getBranch(): string
    {
        return $this->branch;
    }

    /**
     * @return string|null
     */
    public function getTypeLong(): string
    {
        return $this->typeLong;
    }

    /**
     * @return string|null
     */
    public function getTypeShort(): string
    {
        return $this->typeShort;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return get_class_vars($this);
    }
}
