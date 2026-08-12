<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserDungeonProgressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserDungeonProgressRepository::class)]
#[ORM\Table(name: 'user_dungeon_progress')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_DUNGEON_DIFFICULTY', fields: ['user', 'dungeonId', 'difficulty'])]
class UserDungeonProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'dungeonProgressEntries')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 32)]
    private string $dungeonId = '';

    #[ORM\Column(length: 16, options: ['default' => 'normal'])]
    private string $difficulty = 'normal';

    #[ORM\Column]
    private int $clearedStage = 0;

    #[ORM\Column(options: ['default' => false])]
    private bool $completionRewardClaimed = false;

    public function isCompletionRewardClaimed(): bool
    {
        return $this->completionRewardClaimed;
    }

    public function setCompletionRewardClaimed(bool $completionRewardClaimed): static
    {
        $this->completionRewardClaimed = $completionRewardClaimed;

        return $this;
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

    public function getDungeonId(): string
    {
        return $this->dungeonId;
    }

    public function setDungeonId(string $dungeonId): static
    {
        $this->dungeonId = $dungeonId;

        return $this;
    }

    public function getDifficulty(): string
    {
        return $this->difficulty;
    }

    public function setDifficulty(string $difficulty): static
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getClearedStage(): int
    {
        return $this->clearedStage;
    }

    public function setClearedStage(int $clearedStage): static
    {
        $this->clearedStage = max(0, $clearedStage);

        return $this;
    }
}
