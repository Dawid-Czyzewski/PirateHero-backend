<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserWeekendTournamentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserWeekendTournamentRepository::class)]
#[ORM\Table(name: 'user_weekend_tournament')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_WEEKEND_TOURNAMENT', fields: ['user', 'eventStart'])]
class UserWeekendTournament
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $eventStart;

    #[ORM\Column]
    private int $points = 0;

    #[ORM\Column(options: ['default' => false])]
    private bool $rewardClaimed = false;

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

    public function getEventStart(): \DateTimeImmutable
    {
        return $this->eventStart;
    }

    public function setEventStart(\DateTimeImmutable $eventStart): static
    {
        $this->eventStart = $eventStart;

        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = max(0, $points);

        return $this;
    }

    public function addPoints(int $amount): static
    {
        if ($amount > 0) {
            $this->points += $amount;
        }

        return $this;
    }

    public function isRewardClaimed(): bool
    {
        return $this->rewardClaimed;
    }

    public function setRewardClaimed(bool $rewardClaimed): static
    {
        $this->rewardClaimed = $rewardClaimed;

        return $this;
    }
}
