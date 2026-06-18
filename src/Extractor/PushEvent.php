<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

/**
 * Class represents a push event (web hook).
 *
 * @codeCoverageIgnore
 */
class PushEvent implements \JsonSerializable
{
    /**
     * PushEvent constructor.
     */
    public function __construct(
        protected string $repositoryUrl,
        protected string $versionString,
        protected string $urlToComposerFile,
        protected string $payload,
    ) {
    }

    public function getRepositoryUrl(): string
    {
        return $this->repositoryUrl;
    }

    public function getVersionString(): string
    {
        return $this->versionString;
    }

    public function getUrlToComposerFile(): string
    {
        return $this->urlToComposerFile;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }

    public static function fromJson(string $data): self
    {
        $deserializedJson = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

        return new self($deserializedJson['repositoryUrl'], $deserializedJson['versionString'], $deserializedJson['urlToComposerFile'], $deserializedJson['payload']);
    }
}
