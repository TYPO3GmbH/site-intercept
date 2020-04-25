<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Entity;

use Cron\CronExpression;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DiscordScheduledMessageRepository")
 * @codeCoverageIgnore
 */
class DiscordScheduledMessage
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $name = '';

    /**
     * @ORM\Column(type="string", length=2000)
     */
    private string $message = '';

    /**
     * @ManyToOne(targetEntity="DiscordChannel", inversedBy="scheduledMessages")
     * @JoinColumn(name="channel_id", referencedColumnName="channel_id", nullable=true)
     */
    private ?DiscordChannel $channel = null;

    /**
     *
     * @ORM\Column(type="cron_expression")
     */
    private ?CronExpression $schedule = null;

    /**
     * @ORM\Column(type="string", length=75)
     */
    private string $timezone = '';

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $username = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $avatarUrl = null;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return DiscordScheduledMessage
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
     * @return DiscordScheduledMessage
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return DiscordScheduledMessage
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;
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
     * @param DiscordChannel|null $channel
     * @return DiscordScheduledMessage
     */
    public function setChannel(?DiscordChannel $channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * @return CronExpression
     */
    public function getSchedule(): ?CronExpression
    {
        return $this->schedule;
    }

    /**
     * @param CronExpression $schedule
     * @return DiscordScheduledMessage
     */
    public function setSchedule(CronExpression $schedule): self
    {
        $this->schedule = $schedule;
        return $this;
    }

    /**
     * @return string
     */
    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    /**
     * @param $timezone
     * @return DiscordScheduledMessage
     */
    public function setTimezone($timezone): self
    {
        $this->timezone = $timezone;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string|null $username
     * @return DiscordScheduledMessage
     */
    public function setUsername(?string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    /**
     * @param string|null $avatarUrl
     * @return DiscordScheduledMessage
     */
    public function setAvatarUrl(?string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }
}
