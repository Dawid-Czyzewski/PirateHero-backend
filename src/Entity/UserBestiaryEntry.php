<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserBestiaryEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserBestiaryEntryRepository::class)]
#[ORM\Table(name: 'user_bestiary_entry')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_BESTIARY_DUNGEON_STAGE', fields: ['user', 'dungeonId', 'stage'])]
class UserBestiaryEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 32)]
    private string $dungeonId = '';

    #[ORM\Column]
    private int $stage = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $defeatedAt = null;

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

    public function getDungeonId(): string
    {
        return $this->dungeonId;
    }

    public function setDungeonId(string $dungeonId): static
    {
        $this->dungeonId = $dungeonId;

        return $this;
    }

    public function getStage(): int
    {
        return $this->stage;
    }

    public function setStage(int $stage): static
    {
        $this->stage = $stage;

        return $this;
    }

    public function getDefeatedAt(): ?\DateTimeImmutable
    {
        return $this->defeatedAt;
    }

    public function setDefeatedAt(?\DateTimeImmutable $defeatedAt): static
    {
        $this->defeatedAt = $defeatedAt;

        return $this;
    }
}
