<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserBestiaryTrophyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserBestiaryTrophyRepository::class)]
#[ORM\Table(name: 'user_bestiary_trophy')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_BESTIARY_TROPHY', fields: ['user', 'trophyCode'])]
class UserBestiaryTrophy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 64)]
    private string $trophyCode = '';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $unlockedAt;

    #[ORM\Column(options: ['default' => false])]
    private bool $rewardClaimed = false;

    public function __construct()
    {
        $this->unlockedAt = new \DateTimeImmutable();
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

    public function getTrophyCode(): string
    {
        return $this->trophyCode;
    }

    public function setTrophyCode(string $trophyCode): static
    {
        $this->trophyCode = $trophyCode;

        return $this;
    }

    public function getUnlockedAt(): \DateTimeImmutable
    {
        return $this->unlockedAt;
    }

    public function setUnlockedAt(\DateTimeImmutable $unlockedAt): static
    {
        $this->unlockedAt = $unlockedAt;

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
