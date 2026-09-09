<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ShipVoyageStatus;
use App\Repository\ShipVoyageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ShipVoyageRepository::class)]
#[ORM\Table(name: 'ship_voyage')]
class ShipVoyage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ship::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Ship $ship = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $startedBy = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $durationSeconds = 7200;

    #[ORM\Column]
    private int $goldCost = 0;

    #[ORM\Column]
    private int $baseGold = 0;

    #[ORM\Column]
    private int $baseExp = 0;

    #[ORM\Column]
    private int $enrolledCount = 0;

    #[ORM\Column(length: 16)]
    private string $status = ShipVoyageStatus::Active->value;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShip(): ?Ship
    {
        return $this->ship;
    }

    public function setShip(?Ship $ship): static
    {
        $this->ship = $ship;

        return $this;
    }

    public function getStartedBy(): ?User
    {
        return $this->startedBy;
    }

    public function setStartedBy(?User $startedBy): static
    {
        $this->startedBy = $startedBy;

        return $this;
    }

    public function getDurationSeconds(): int
    {
        return $this->durationSeconds;
    }

    public function setDurationSeconds(int $durationSeconds): static
    {
        $this->durationSeconds = $durationSeconds;

        return $this;
    }

    public function getGoldCost(): int
    {
        return $this->goldCost;
    }

    public function setGoldCost(int $goldCost): static
    {
        $this->goldCost = $goldCost;

        return $this;
    }

    public function getBaseGold(): int
    {
        return $this->baseGold;
    }

    public function setBaseGold(int $baseGold): static
    {
        $this->baseGold = $baseGold;

        return $this;
    }

    public function getBaseExp(): int
    {
        return $this->baseExp;
    }

    public function setBaseExp(int $baseExp): static
    {
        $this->baseExp = $baseExp;

        return $this;
    }

    public function getEnrolledCount(): int
    {
        return $this->enrolledCount;
    }

    public function setEnrolledCount(int $enrolledCount): static
    {
        $this->enrolledCount = max(0, $enrolledCount);

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string|ShipVoyageStatus $status): static
    {
        $this->status = $status instanceof ShipVoyageStatus ? $status->value : $status;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeInterface $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === ShipVoyageStatus::Active->value;
    }
}
