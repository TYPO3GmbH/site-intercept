<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Entity;

use App\Extractor\PushEvent;
use App\Repository\DocumentationQuarantineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @codeCoverageIgnore
 */
#[ORM\Entity(repositoryClass: DocumentationQuarantineRepository::class)]
#[ORM\Index(name: 'checksum_idx', columns: ['checksum'])]
class DocumentationQuarantine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private string $domain;

    #[ORM\Column(type: Types::JSON)]
    #[Assert\NotBlank]
    private string $serializedPushEvent;

    #[ORM\Column(type: Types::STRING, length: 32, options: ['fixed' => true])]
    #[Assert\NotBlank]
    private string $checksum;

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

    public function getSerializedPushEvent(): string
    {
        return $this->serializedPushEvent;
    }

    public function getPushEvent(): PushEvent
    {
        return PushEvent::fromJson($this->serializedPushEvent);
    }

    public function setSerializedPushEvent(string $serializedPushEvent): self
    {
        $this->serializedPushEvent = $serializedPushEvent;

        return $this;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function setChecksum(string $checksum): self
    {
        $this->checksum = $checksum;

        return $this;
    }
}
