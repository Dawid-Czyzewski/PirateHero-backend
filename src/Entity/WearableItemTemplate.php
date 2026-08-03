<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;
use App\Repository\WearableItemTemplateRepository;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: WearableItemTemplateRepository::class)]
#[ORM\Table(name: 'wearable_item_template')]
#[ORM\UniqueConstraint(name: 'UNIQ_WEARABLE_ITEM_TEMPLATE_CODE', columns: ['public_code'])]
class WearableItemTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private string $publicCode;

    #[ORM\Column(enumType: WearableItemType::class)]
    private WearableItemType $type;

    #[ORM\Column(length: 160)]
    private string $nameKey;

    #[ORM\Column(length: 64)]
    private string $imageKey;

    #[ORM\Column(enumType: WearableItemRarity::class)]
    private WearableItemRarity $rarity;

    #[ORM\Column]
    private int $minLevel = 1;

    #[ORM\Column]
    private int $maxLevel = 10;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicCode(): string
    {
        return $this->publicCode;
    }

    public function setPublicCode(string $publicCode): static
    {
        $this->publicCode = $publicCode;

        return $this;
    }

    public function getType(): WearableItemType
    {
        return $this->type;
    }

    public function setType(WearableItemType $type): static
    {
        $this->type = $type;

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

    public function getImageKey(): string
    {
        return $this->imageKey;
    }

    public function setImageKey(string $imageKey): static
    {
        $this->imageKey = $imageKey;

        return $this;
    }

    public function getRarity(): WearableItemRarity
    {
        return $this->rarity;
    }

    public function setRarity(WearableItemRarity $rarity): static
    {
        $this->rarity = $rarity;

        return $this;
    }

    public function getMinLevel(): int
    {
        return $this->minLevel;
    }

    public function setMinLevel(int $minLevel): static
    {
        $this->minLevel = $minLevel;

        return $this;
    }

    public function getMaxLevel(): int
    {
        return $this->maxLevel;
    }

    public function setMaxLevel(int $maxLevel): static
    {
        $this->maxLevel = $maxLevel;

        return $this;
    }

    public function isAvailableForLevel(int $playerLevel): bool
    {
        return $playerLevel >= $this->minLevel && $playerLevel <= $this->maxLevel;
    }
}
