<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Entity;

use App\Enum\HistoryEntryType;
use App\Repository\HistoryEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HistoryEntryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class HistoryEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(type: Types::STRING, enumType: HistoryEntryType::class)]
    private HistoryEntryType $type;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $status;

    /**
     * Allow grouping of history entries.
     */
    #[ORM\Column(type: Types::STRING, length: 255, options: ['default' => 'default'])]
    private string $groupEntry = 'default';

    #[ORM\Column(type: Types::JSON)]
    private array $data = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?HistoryEntryType
    {
        return $this->type;
    }

    public function setType(HistoryEntryType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getGroupEntry(): ?string
    {
        return $this->groupEntry;
    }

    public function setGroupEntry(string $groupEntry): self
    {
        $this->groupEntry = $groupEntry;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
