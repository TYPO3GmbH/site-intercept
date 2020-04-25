<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Entity;

use App\Enum\DocumentationStatus;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DocumentationJarRepository")
 * @ORM\HasLifecycleCallbacks
 * @codeCoverageIgnore
 */
class DocumentationJar
{
    public const VALID_REPOSITORY_URL_REGEX = '/^https:\/\/[-a-zA-Z0-9_.-\/]{2,300}\.git$/';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Url
     */
    private string $repositoryUrl;

    /**
     * @ORM\Column(type="string", length=255, options={"default": ""})
     * @Assert\NotBlank
     * @Assert\Url
     */
    private string $publicComposerJsonUrl;

    /**
     * @ORM\Column(type="string", length=255, options={"default": ""})
     */
    private string $vendor;

    /**
     * @ORM\Column(type="string", length=255, options={"default": ""})
     */
    private string $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $packageName;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     */
    private string $packageType;

    /**
     * @ORM\Column(type="string", length=255, options={"default": ""}, nullable=true)
     */
    private ?string $extensionKey = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $branch;

    /**
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     */
    private \DateTimeInterface $createdAt;

    /**
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     */
    private \DateTimeInterface $lastRenderedAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $targetBranchDirectory;

    /**
     * @ORM\Column(type="string", length=255, options={"default": ""})
     */
    private string $typeLong;

    /**
     * @ORM\Column(type="string", length=255, options={"default": ""})
     */
    private string $typeShort;

    /**
     * @ORM\Column(type="string", length=20, options={"default": ""})
     */
    private string $minimumTypoVersion;

    /**
     * @ORM\Column(type="string", length=20, options={"default": ""})
     */
    private string $maximumTypoVersion;

    /**
     * @ORM\Column(type="integer", options={"default": "0"})
     */
    private int $status;

    /**
     * @ORM\Column(type="string", length=255, options={"default": ""})
     */
    private string $buildKey;

    /**
     * @ORM\Column(type="boolean", options={"default": "0"})
     */
    private bool $reRenderNeeded;

    /**
     * @ORM\Column(type="boolean", options={"default": "0"})
     */
    private bool $new;

    /**
     * @ORM\Column(type="boolean", options={"default": "1"})
     */
    private bool $approved;

    /**
     * @return bool
     */
    public function isReRenderNeeded(): bool
    {
        return (bool)$this->reRenderNeeded;
    }

    /**
     * @param bool $needed
     * @return DocumentationJar
     */
    public function setReRenderNeeded(bool $needed): self
    {
        $this->reRenderNeeded = $needed;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTypeShort(): ?string
    {
        return $this->typeShort;
    }

    /**
     * @param mixed $typeShort
     * @return DocumentationJar
     */
    public function setTypeShort(string $typeShort): self
    {
        $this->typeShort = $typeShort;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTypeLong(): ?string
    {
        return $this->typeLong;
    }

    /**
     * @param mixed $typeLong
     * @return DocumentationJar
     */
    public function setTypeLong(string $typeLong): self
    {
        $this->typeLong = $typeLong;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getRepositoryUrl(): ?string
    {
        return $this->repositoryUrl;
    }

    /**
     * @param string $repositoryUrl
     * @return DocumentationJar
     */
    public function setRepositoryUrl(string $repositoryUrl): self
    {
        $this->repositoryUrl = $repositoryUrl;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPublicComposerJsonUrl(): ?string
    {
        return $this->publicComposerJsonUrl;
    }

    /**
     * @param string $publicComposerJsonUrl
     * @return DocumentationJar
     */
    public function setPublicComposerJsonUrl(string $publicComposerJsonUrl): self
    {
        $this->publicComposerJsonUrl = $publicComposerJsonUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getVendor(): string
    {
        return $this->vendor;
    }

    /**
     * @param mixed $vendor
     * @return DocumentationJar
     */
    public function setVendor(string $vendor): self
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return DocumentationJar
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getPackageName(): string
    {
        return $this->packageName;
    }

    /**
     * @param string $packageName
     * @return DocumentationJar
     */
    public function setPackageName(string $packageName): self
    {
        $this->packageName = $packageName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPackageType(): string
    {
        return $this->packageType;
    }

    /**
     * @param mixed $packageType
     * @return DocumentationJar
     */
    public function setPackageType(string $packageType): self
    {
        $this->packageType = $packageType;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getExtensionKey(): ?string
    {
        return $this->extensionKey;
    }

    /**
     * @param mixed $extensionKey
     * @return DocumentationJar
     */
    public function setExtensionKey(?string $extensionKey): self
    {
        $this->extensionKey = $extensionKey;
        return $this;
    }

    public function getBranch(): ?string
    {
        return $this->branch;
    }

    /**
     * @param string $branch
     * @return DocumentationJar
     */
    public function setBranch(string $branch): self
    {
        $this->branch = $branch;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getLastRenderedAt(): ?\DateTimeInterface
    {
        return $this->lastRenderedAt;
    }

    public function setLastRenderedAt(\DateTimeInterface $lastRenderedAt): self
    {
        $this->lastRenderedAt = $lastRenderedAt;

        return $this;
    }

    public function getTargetBranchDirectory(): ?string
    {
        return $this->targetBranchDirectory;
    }

    public function setTargetBranchDirectory(string $targetBranchDirectory): self
    {
        $this->targetBranchDirectory = $targetBranchDirectory;

        return $this;
    }

    public function getMinimumTypoVersion(): ?string
    {
        return $this->minimumTypoVersion;
    }

    public function setMinimumTypoVersion(string $minimumTypoVersion): self
    {
        $this->minimumTypoVersion = $minimumTypoVersion;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getMaximumTypoVersion(): ?string
    {
        return $this->maximumTypoVersion;
    }

    public function setMaximumTypoVersion(string $maximumTypoVersion): self
    {
        $this->maximumTypoVersion = $maximumTypoVersion;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBuildKey(): ?string
    {
        return $this->buildKey;
    }

    public function setNew(bool $new): self
    {
        $this->new = $new;

        return $this;
    }

    public function isNew(): bool
    {
        return (bool)$this->new;
    }

    public function setApproved(bool $approved): self
    {
        $this->approved = $approved;

        return $this;
    }

    public function isApproved(): bool
    {
        return (bool)$this->approved;
    }

    /**
     * @param string $buildKey
     * @return self
     */
    public function setBuildKey(string $buildKey): self
    {
        $this->buildKey = $buildKey;

        return $this;
    }

    public function isRenderable(): bool
    {
        return !empty($this->publicComposerJsonUrl)
            && in_array($this->status, [
                DocumentationStatus::STATUS_RENDERED,
                DocumentationStatus::STATUS_RENDERING_FAILED
            ], true);
    }

    public function isDeletable(): bool
    {
        return $this->status === DocumentationStatus::STATUS_RENDERED || $this->status === DocumentationStatus::STATUS_RENDERING_FAILED;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps(): void
    {
        // Set created at if record is first persisted
        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt(new \DateTime('now'));
        }
        // Update last rendered if record is first persisted
        if ($this->getLastRenderedAt() === null) {
            $this->setLastRenderedAt(new \DateTime('now'));
        }
    }
}
