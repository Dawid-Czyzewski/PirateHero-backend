<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserCompanionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserCompanionRepository::class)]
#[ORM\Table(name: 'user_companion')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_COMPANION_TEMPLATE', fields: ['user', 'companionTemplate'])]
class UserCompanion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: CompanionTemplate::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CompanionTemplate $companionTemplate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $unlockedAt = null;

    #[ORM\Column]
    private int $level = 1;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastFedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastAbilityAt = null;

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

    public function getCompanionTemplate(): ?CompanionTemplate
    {
        return $this->companionTemplate;
    }

    public function setCompanionTemplate(?CompanionTemplate $companionTemplate): static
    {
        $this->companionTemplate = $companionTemplate;

        return $this;
    }

    public function getUnlockedAt(): ?\DateTimeImmutable
    {
        return $this->unlockedAt;
    }

    public function setUnlockedAt(\DateTimeImmutable $unlockedAt): static
    {
        $this->unlockedAt = $unlockedAt;

        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getLastFedAt(): ?\DateTimeImmutable
    {
        return $this->lastFedAt;
    }

    public function setLastFedAt(?\DateTimeImmutable $lastFedAt): static
    {
        $this->lastFedAt = $lastFedAt;

        return $this;
    }

    public function getLastAbilityAt(): ?\DateTimeImmutable
    {
        return $this->lastAbilityAt;
    }

    public function setLastAbilityAt(?\DateTimeImmutable $lastAbilityAt): static
    {
        $this->lastAbilityAt = $lastAbilityAt;

        return $this;
    }
}
