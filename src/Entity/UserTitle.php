<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserTitleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserTitleRepository::class)]
#[ORM\Table(name: 'user_title')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_PLAYER_TITLE', fields: ['user', 'playerTitle'])]
class UserTitle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: PlayerTitle::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PlayerTitle $playerTitle = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $unlockedAt = null;

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

    public function getPlayerTitle(): ?PlayerTitle
    {
        return $this->playerTitle;
    }

    public function setPlayerTitle(?PlayerTitle $playerTitle): static
    {
        $this->playerTitle = $playerTitle;

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
}
