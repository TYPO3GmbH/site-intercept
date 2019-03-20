<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DocumentationJarRepository")
 */
class DocumentationJar
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $repositoryUrl;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $packageName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $branch;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRepositoryUrl(): ?string
    {
        return $this->repositoryUrl;
    }

    public function setRepositoryUrl(string $repositoryUrl): self
    {
        $this->repositoryUrl = $repositoryUrl;

        return $this;
    }

    public function getPackageName(): ?string
    {
        return $this->packageName;
    }

    public function setPackageName(string $packageName): self
    {
        $this->packageName = $packageName;

        return $this;
    }

    public function getBranch(): ?string
    {
        return $this->branch;
    }

    public function setBranch(string $branch): self
    {
        $this->branch = $branch;

        return $this;
    }
}
