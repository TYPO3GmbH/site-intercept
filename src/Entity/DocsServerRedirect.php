<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Entity;

use App\Exception\InvalidStatusException;
use App\Validator\Constraints as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This is a stupid simple entity class and represent a redirect.
 *
 * @ORM\Entity(repositoryClass="App\Repository\DocsServerRedirectRepository")
 * @ORM\Table("redirect")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity("source")
 * @codeCoverageIgnore
 */
class DocsServerRedirect
{
    public const STATUS_CODE_302 = 302; // Found
    public const STATUS_CODE_303 = 303; // See Other
    public const STATUS_CODE_307 = 307; // Temporary Redirect

    public static array $allowedStatusCodes = [
        self::STATUS_CODE_302,
        self::STATUS_CODE_303,
        self::STATUS_CODE_307,
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private ?\DateTimeInterface $createdAt = null;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @ORM\Column(type="string", length=2000)
     * @Assert\Regex(
     *     pattern="@^/(([pcmh]{1}|other)/([^/]*)/([^/]*)/([^/]*)/(.*)|typo3cms/extensions/([^/]*)/([^/]*)/)$@m",
     *     message="The path doesn't match the required format"
     * )
     * @AppAssert\InvalidCharacter
     */
    private string $source = '';

    /**
     * @ORM\Column(type="string", length=2000)
     * @Assert\Regex(
     *     pattern="@^/([pcmh]{1}|other)/([^/]*)/([^/]*)/([^/]*)/(.*)$@m",
     *     message="The path doesn't match the required format"
     * )
     * @AppAssert\InvalidCharacter
     */
    private string $target = '';

    /**
     * @ORM\Column(name="is_legacy", type="integer")
     */
    private bool $isLegacy = false;

    /**
     * @ORM\Column(type="integer")
     */
    private int $statusCode = self::STATUS_CODE_303;

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * @throws \Exception
     */
    public function updatedTimestamps(): void
    {
        $this->setUpdatedAt(new \DateTime(date('Y-m-d H:i:s')));
        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt(new \DateTime(date('Y-m-d H:i:s')));
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setTarget(string $target): self
    {
        $this->target = $target;
        return $this;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): self
    {
        if (!in_array($statusCode, self::$allowedStatusCodes, true)) {
            throw new InvalidStatusException('The HTTP status code is invalid for a redirect', 1553001673);
        }
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getIsLegacy(): bool
    {
        return $this->isLegacy;
    }

    public function setIsLegacy(bool $isLegacy): self
    {
        $this->isLegacy = $isLegacy;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
