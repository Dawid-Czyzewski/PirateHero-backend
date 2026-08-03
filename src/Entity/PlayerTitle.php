<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TitleUnlockType;
use App\Repository\PlayerTitleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerTitleRepository::class)]
#[ORM\Table(name: 'player_title')]
class PlayerTitle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $code = '';

    #[ORM\Column(length: 128)]
    private string $nameKey = '';

    #[ORM\Column(length: 128)]
    private string $descriptionKey = '';

    #[ORM\Column(length: 32, enumType: TitleUnlockType::class)]
    private TitleUnlockType $unlockType = TitleUnlockType::GAME_START;

    #[ORM\Column(nullable: true)]
    private ?int $unlockValue = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $unlockDungeonId = null;

    #[ORM\Column]
    private int $sortOrder = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getNameKey(): string
    {
        return $this->nameKey;
    }

    public function setNameKey(string $nameKey): static
    {
        $this->nameKey = $nameKey;

        return $this;
    }

    public function getDescriptionKey(): string
    {
        return $this->descriptionKey;
    }

    public function setDescriptionKey(string $descriptionKey): static
    {
        $this->descriptionKey = $descriptionKey;

        return $this;
    }

    public function getUnlockType(): TitleUnlockType
    {
        return $this->unlockType;
    }

    public function setUnlockType(TitleUnlockType $unlockType): static
    {
        $this->unlockType = $unlockType;

        return $this;
    }

    public function getUnlockValue(): ?int
    {
        return $this->unlockValue;
    }

    public function setUnlockValue(?int $unlockValue): static
    {
        $this->unlockValue = $unlockValue;

        return $this;
    }

    public function getUnlockDungeonId(): ?string
    {
        return $this->unlockDungeonId;
    }

    public function setUnlockDungeonId(?string $unlockDungeonId): static
    {
        $this->unlockDungeonId = $unlockDungeonId;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }
}
