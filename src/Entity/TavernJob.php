<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TavernJobRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TavernJobRepository::class)]
#[ORM\Table(name: 'tavern_job')]
class TavernJob
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 64)]
    private string $titleKey = '';

    #[ORM\Column]
    private int $durationMinutes = 15;

    #[ORM\Column]
    private int $rewardGold = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $rewardDiamonds = 0;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $offeredOn;

    #[ORM\Column(options: ['default' => false])]
    private bool $started = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $completed = false;

    public function __construct()
    {
        $this->offeredOn = new \DateTimeImmutable('today');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getTitleKey(): string
    {
        return $this->titleKey;
    }

    public function setTitleKey(string $titleKey): static
    {
        $this->titleKey = $titleKey;

        return $this;
    }

    public function getDurationMinutes(): int
    {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(int $durationMinutes): static
    {
        $this->durationMinutes = $durationMinutes;

        return $this;
    }

    public function getRewardGold(): int
    {
        return $this->rewardGold;
    }

    public function setRewardGold(int $rewardGold): static
    {
        $this->rewardGold = $rewardGold;

        return $this;
    }

    public function getRewardDiamonds(): int
    {
        return $this->rewardDiamonds;
    }

    public function setRewardDiamonds(int $rewardDiamonds): static
    {
        $this->rewardDiamonds = $rewardDiamonds;

        return $this;
    }

    public function getOfferedOn(): \DateTimeImmutable
    {
        return $this->offeredOn;
    }

    public function setOfferedOn(\DateTimeImmutable $offeredOn): static
    {
        $this->offeredOn = $offeredOn;

        return $this;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function setStarted(bool $started): static
    {
        $this->started = $started;

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): static
    {
        $this->completed = $completed;

        return $this;
    }
}
