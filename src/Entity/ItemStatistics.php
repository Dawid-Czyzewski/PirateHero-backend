<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ItemStatisticsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ItemStatisticsRepository::class)]
class ItemStatistics
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $strongPoints = 0;

    #[ORM\Column]
    private int $agilityPoints = 0;

    #[ORM\Column]
    private int $criticalChancePoints = 0;

    #[ORM\Column]
    private int $intelligencePoints = 0;

    #[ORM\Column]
    private int $healthPoints = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(['user:read'])]
    public function getStrongPoints(): int
    {
        return $this->strongPoints;
    }

    public function setStrongPoints(int $strongPoints): static
    {
        $this->strongPoints = $strongPoints;

        return $this;
    }

    #[Groups(['user:read'])]
    public function getAgilityPoints(): int
    {
        return $this->agilityPoints;
    }

    public function setAgilityPoints(int $agilityPoints): static
    {
        $this->agilityPoints = $agilityPoints;

        return $this;
    }

    #[Groups(['user:read'])]
    public function getCriticalChancePoints(): int
    {
        return $this->criticalChancePoints;
    }

    public function setCriticalChancePoints(int $criticalChancePoints): static
    {
        $this->criticalChancePoints = $criticalChancePoints;

        return $this;
    }

    #[Groups(['user:read'])]
    public function getIntelligencePoints(): int
    {
        return $this->intelligencePoints;
    }

    public function setIntelligencePoints(int $intelligencePoints): static
    {
        $this->intelligencePoints = $intelligencePoints;

        return $this;
    }

    #[Groups(['user:read'])]
    public function getHealthPoints(): int
    {
        return $this->healthPoints;
    }

    public function setHealthPoints(int $healthPoints): static
    {
        $this->healthPoints = $healthPoints;

        return $this;
    }

    /**
     * Manual API payloads (preview, quest, coupon) — scalar columns only.
     *
     * @return array<string, int>
     */
    public function toClientArray(): array
    {
        return [
            'strongPoints' => $this->strongPoints,
            'agilityPoints' => $this->agilityPoints,
            'criticalChancePoints' => $this->criticalChancePoints,
            'intelligencePoints' => $this->intelligencePoints,
            'healthPoints' => $this->healthPoints,
        ];
    }
}
