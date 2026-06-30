<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Entity;

use App\Enum\RepositoryDomainStatus;
use App\Repository\KnownRepositoryDomainRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @codeCoverageIgnore
 */
#[ORM\Entity(repositoryClass: KnownRepositoryDomainRepository::class)]
#[ORM\Index(name: 'domain_idx', columns: ['domain'])]
class KnownRepositoryDomain
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private string $domain;

    #[ORM\Column(type: Types::INTEGER, enumType: RepositoryDomainStatus::class)]
    #[Assert\NotBlank]
    private RepositoryDomainStatus $status;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $locked = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastHit = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getStatus(): RepositoryDomainStatus
    {
        return $this->status;
    }

    public function setStatus(RepositoryDomainStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isAllowed(): bool
    {
        return RepositoryDomainStatus::ALLOWED === $this->status;
    }

    public function isDisallowed(): bool
    {
        return RepositoryDomainStatus::DISALLOWED === $this->status;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): void
    {
        $this->locked = $locked;
    }

    public function getLastHit(): ?\DateTimeInterface
    {
        return $this->lastHit;
    }

    public function setLastHit(?\DateTimeInterface $lastHit): self
    {
        $this->lastHit = $lastHit;

        return $this;
    }
}
