<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserDailyChallengeDayRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserDailyChallengeDayRepository::class)]
#[ORM\Table(name: 'user_daily_challenge_day')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_DAILY_CHALLENGE_DAY', fields: ['user', 'challengeDate'])]
class UserDailyChallengeDay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $challengeDate;

    #[ORM\Column(options: ['default' => false])]
    private bool $bonusClaimed = false;

    public function __construct()
    {
        $this->challengeDate = new \DateTimeImmutable('today');
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

    public function getChallengeDate(): \DateTimeImmutable
    {
        return $this->challengeDate;
    }

    public function setChallengeDate(\DateTimeImmutable $challengeDate): static
    {
        $this->challengeDate = $challengeDate;

        return $this;
    }

    public function isBonusClaimed(): bool
    {
        return $this->bonusClaimed;
    }

    public function setBonusClaimed(bool $bonusClaimed): static
    {
        $this->bonusClaimed = $bonusClaimed;

        return $this;
    }
}
