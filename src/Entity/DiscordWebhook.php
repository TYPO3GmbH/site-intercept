<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DiscordWebhookRepository")
 * @codeCoverageIgnore
 */
class DiscordWebhook
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
    private $name;

    /**
     * @ManyToOne(targetEntity="DiscordChannel", inversedBy="webhooks")
     * @JoinColumn(name="channel_id", referencedColumnName="channel_id", nullable=true)
     */
    private $channel;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $identifier;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255, options={"default": "Intercept"})
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $avatarUrl;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $logLevel;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return DiscordWebhook
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return DiscordWebhook
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return DiscordChannel|null
     */
    public function getChannel(): ?DiscordChannel
    {
        return $this->channel;
    }

    /**
     * @param DiscordChannel $channel
     * @return DiscordWebhook
     */
    public function setChannel(?DiscordChannel $channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return DiscordWebhook
     */
    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): ?int
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return DiscordWebhook
     */
    public function setType(int $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return DiscordWebhook
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    /**
     * @param string $avatarUrl
     * @return DiscordWebhook
     */
    public function setAvatarUrl(string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getLogLevel(): ?int
    {
        return $this->logLevel;
    }

    /**
     * @param int|null $logLevel
     * @return DiscordWebhook
     */
    public function setLogLevel(?int $logLevel): self
    {
        $this->logLevel = $logLevel;
        return $this;
    }
}
