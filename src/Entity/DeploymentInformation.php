<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Entity;

/**
 * Class DeploymentInformation
 * This is a standalone entity for deployment information and not related to ORM, yet.
 *
 */
class DeploymentInformation
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $vendor;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $typeLong;

    /**
     * @var string
     */
    private $typeShort;

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getVendor(): ?string
    {
        return $this->vendor;
    }

    /**
     * @param string $vendor
     * @return self
     */
    public function setVendor(string $vendor): self
    {
        $this->vendor = $vendor;
        return $this;
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
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return self
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTypeLong(): ?string
    {
        return $this->typeLong;
    }

    /**
     * @param string $typeLong
     * @return self
     */
    public function setTypeLong(string $typeLong): self
    {
        $this->typeLong = $typeLong;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTypeShort(): ?string
    {
        return $this->typeShort;
    }

    /**
     * @param string $typeShort
     * @return self
     */
    public function setTypeShort(string $typeShort): self
    {
        $this->typeShort = $typeShort;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return get_class_vars($this);
    }
}
