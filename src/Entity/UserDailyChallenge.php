<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserDailyChallengeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserDailyChallengeRepository::class)]
#[ORM\Table(name: 'user_daily_challenge')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_DAILY_CHALLENGE_SLOT', fields: ['user', 'challengeDate', 'slot'])]
class UserDailyChallenge
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

    #[ORM\Column(type: Types::SMALLINT)]
    private int $slot = 1;

    #[ORM\Column(length: 32)]
    private string $type = '';

    #[ORM\Column]
    private int $targetValue = 1;

    #[ORM\Column]
    private int $progress = 0;

    #[ORM\Column(options: ['default' => false])]
    private bool $rewardClaimed = false;

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

    public function getSlot(): int
    {
        return $this->slot;
    }

    public function setSlot(int $slot): static
    {
        $this->slot = $slot;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTargetValue(): int
    {
        return $this->targetValue;
    }

    public function setTargetValue(int $targetValue): static
    {
        $this->targetValue = max(1, $targetValue);

        return $this;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): static
    {
        $this->progress = max(0, $progress);

        return $this;
    }

    public function addProgress(int $amount): static
    {
        if ($amount > 0) {
            $this->progress = min($this->targetValue, $this->progress + $amount);
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

    public function isComplete(): bool
    {
        return $this->progress >= $this->targetValue;
    }
}
