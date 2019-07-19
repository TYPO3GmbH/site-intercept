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
use Doctrine\ORM\Mapping\OneToMany;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DiscordChannelRepository")
 * @codeCoverageIgnore
 */
class DiscordChannel
{
    public const CHANNEL_TYPE_TEXT = 0;

    public const CHANNEL_TYPE_VOICE = 2;

    public const CHANNEL_TYPE_CATEGORY = 4;

    /**
     * @ORM\Id()
     * @ORM\Column(type="string", length=255)
     */
    private $channelId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $channelName;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $channelType;

    /**
     * @ManyToOne(targetEntity="DiscordChannel", inversedBy="children")
     * @JoinColumn(name="parent_id", referencedColumnName="channel_id")
     */
    private $parent;

    /**
     * @OneToMany(targetEntity="DiscordChannel", mappedBy="parent", cascade={"remove"})
     */
    private $children;

    /**
     * @OneToMany(targetEntity="DiscordWebhook", mappedBy="channel", cascade={"remove"})
     */
    private $webhooks;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $webhookUrl;

    /**
     * @return string
     */
    public function getChannelId(): string
    {
        return $this->channelId;
    }

    /**
     * @param string $channelId
     * @return DiscordChannel
     */
    public function setChannelId(string $channelId): self
    {
        $this->channelId = $channelId;
        return $this;
    }

    /**
     * @return string
     */
    public function getChannelName(): string
    {
        return $this->channelName;
    }

    /**
     * @param string $channelName
     * @return DiscordChannel
     */
    public function setChannelName(string $channelName): self
    {
        $this->channelName = $channelName;
        return $this;
    }

    /**
     * @return int
     */
    public function getChannelType(): int
    {
        return $this->channelType;
    }

    /**
     * @param int $channelType
     * @return DiscordChannel
     */
    public function setChannelType(int $channelType): self
    {
        $this->channelType = $channelType;
        return $this;
    }

    /**
     * @return DiscordChannel|null
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @param DiscordChannel|null $parent
     * @return DiscordChannel
     */
    public function setParent(?self $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getWebhookUrl(): ?string
    {
        return $this->webhookUrl;
    }

    /**
     * @param string $webhookUrl
     * @return DiscordChannel
     */
    public function setWebhookUrl(?string $webhookUrl): self
    {
        $this->webhookUrl = $webhookUrl;
        return $this;
    }
}
