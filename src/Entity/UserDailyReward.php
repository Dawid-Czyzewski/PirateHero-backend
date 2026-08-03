<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserDailyRewardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserDailyRewardRepository::class)]
#[ORM\Table(name: 'user_daily_reward')]
class UserDailyReward
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'dailyRewardProgress')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(options: ['default' => 1])]
    private int $nextDay = 1;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastClaimDate = null;

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

    public function getNextDay(): int
    {
        return $this->nextDay;
    }

    public function setNextDay(int $nextDay): static
    {
        $this->nextDay = $nextDay;

        return $this;
    }

    public function getLastClaimDate(): ?\DateTimeInterface
    {
        return $this->lastClaimDate;
    }

    public function setLastClaimDate(?\DateTimeInterface $lastClaimDate): static
    {
        $this->lastClaimDate = $lastClaimDate;

        return $this;
    }
}
