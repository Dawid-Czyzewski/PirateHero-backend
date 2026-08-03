<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;
use App\Repository\WearableItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: WearableItemRepository::class)]
class WearableItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Groups(['user:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 160, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $nameKey = null;

    #[ORM\Column(enumType: WearableItemType::class)]
    #[Groups(['user:read'])]
    private ?WearableItemType $type = null;

    #[ORM\Column(enumType: WearableItemRarity::class)]
    #[Groups(['user:read'])]
    private ?WearableItemRarity $rarity = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?ItemStatistics $statistics = null;

    #[ORM\OneToOne(mappedBy: 'wearableItem', cascade: ['persist', 'remove'])]
    private ?UserEquipmentSlot $userEquipmentSlot = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $price = null;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $publicCode = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $imageKey = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getNameKey(): ?string
    {
        return $this->nameKey;
    }

    public function setNameKey(?string $nameKey): static
    {
        $this->nameKey = $nameKey;

        return $this;
    }

    public function getType(): ?WearableItemType
    {
        return $this->type;
    }

    public function setType(WearableItemType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getRarity(): ?WearableItemRarity
    {
        return $this->rarity;
    }

    public function setRarity(WearableItemRarity $rarity): static
    {
        $this->rarity = $rarity;

        return $this;
    }

    #[Groups(['user:read'])]
    public function getStatistics(): ?ItemStatistics
    {
        return $this->statistics;
    }

    public function setStatistics(ItemStatistics $statistics): static
    {
        $this->statistics = $statistics;

        return $this;
    }

    public function getUserEquipmentSlot(): ?UserEquipmentSlot
    {
        return $this->userEquipmentSlot;
    }

    public function setUserEquipmentSlot(?UserEquipmentSlot $userEquipmentSlot): static
    {
        if ($userEquipmentSlot === null && $this->userEquipmentSlot !== null) {
            $this->userEquipmentSlot->setWearableItem(null);
        }

        if ($userEquipmentSlot !== null && $userEquipmentSlot->getWearableItem() !== $this) {
            $userEquipmentSlot->setWearableItem($this);
        }

        $this->userEquipmentSlot = $userEquipmentSlot;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getPublicCode(): ?string
    {
        return $this->publicCode;
    }

    public function setPublicCode(?string $publicCode): static
    {
        $this->publicCode = $publicCode;

        return $this;
    }

    public function getImageKey(): ?string
    {
        return $this->imageKey;
    }

    public function setImageKey(?string $imageKey): static
    {
        $this->imageKey = $imageKey;

        return $this;
    }
}
