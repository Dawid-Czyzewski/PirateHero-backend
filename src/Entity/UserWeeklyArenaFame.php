<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserWeeklyArenaFameRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserWeeklyArenaFameRepository::class)]
#[ORM\Table(name: 'user_weekly_arena_fame')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_WEEKLY_ARENA_FAME', fields: ['user', 'weekStart'])]
class UserWeeklyArenaFame
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $weekStart;

    #[ORM\Column]
    private int $fameEarned = 0;

    #[ORM\Column(options: ['default' => false])]
    private bool $claimedTier1 = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $claimedTier2 = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $claimedTier3 = false;

    public function __construct()
    {
        $this->weekStart = new \DateTimeImmutable('monday this week');
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

    public function getWeekStart(): \DateTimeImmutable
    {
        return $this->weekStart;
    }

    public function setWeekStart(\DateTimeImmutable $weekStart): static
    {
        $this->weekStart = $weekStart;

        return $this;
    }

    public function getFameEarned(): int
    {
        return $this->fameEarned;
    }

    public function setFameEarned(int $fameEarned): static
    {
        $this->fameEarned = max(0, $fameEarned);

        return $this;
    }

    public function addFameEarned(int $amount): static
    {
        if ($amount > 0) {
            $this->fameEarned += $amount;
        }

        return $this;
    }

    public function isClaimedTier(int $tier): bool
    {
        return match ($tier) {
            1 => $this->claimedTier1,
            2 => $this->claimedTier2,
            3 => $this->claimedTier3,
            default => false,
        };
    }

    public function setClaimedTier(int $tier, bool $claimed): static
    {
        match ($tier) {
            1 => $this->claimedTier1 = $claimed,
            2 => $this->claimedTier2 = $claimed,
            3 => $this->claimedTier3 = $claimed,
            default => null,
        };

        return $this;
    }
}
