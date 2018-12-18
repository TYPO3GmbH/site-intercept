<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BambooNightlyBuildRepository")
 */
class BambooNightlyBuild
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
    private $buildKey;

    /**
     * @ORM\Column(type="integer", options={"unsigned":true, "default":0})
     */
    private $failedRuns;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBuildKey(): ?string
    {
        return $this->buildKey;
    }

    public function setBuildKey(string $buildKey): self
    {
        $this->buildKey = $buildKey;

        return $this;
    }

    public function getFailedRuns(): ?int
    {
        return $this->failedRuns;
    }

    public function setFailedRuns(int $failedRuns): self
    {
        $this->failedRuns = $failedRuns;

        return $this;
    }
}
