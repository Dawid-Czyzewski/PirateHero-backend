<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\WearableItemType;
use App\Repository\UserEquipmentSlotRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserEquipmentSlotRepository::class)]
class UserEquipmentSlot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(enumType: WearableItemType::class)]
    #[Groups(['user:read'])]
    private ?WearableItemType $type = null;

    #[ORM\ManyToOne(targetEntity: UserEquipment::class, inversedBy: 'userEquipmentSlots')]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserEquipment $userEquipment = null;

    #[ORM\OneToOne(inversedBy: 'userEquipmentSlot', cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private ?WearableItem $wearableItem = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWearableItem(): ?WearableItem
    {
        return $this->wearableItem;
    }

    public function setWearableItem(?WearableItem $wearableItem): static
    {
        $this->wearableItem = $wearableItem;

        return $this;
    }

    public function equip(WearableItem $item): static
    {
        $this->wearableItem = $item;

        return $this;
    }

    public function unequip(): ?WearableItem
    {
        $item = $this->wearableItem;
        $this->wearableItem = null;

        return $item;
    }

    public function getUserEquipment(): ?UserEquipment
    {
        return $this->userEquipment;
    }

    public function setUserEquipment(?UserEquipment $userEquipment): self
    {
        $this->userEquipment = $userEquipment;

        return $this;
    }

    public function setType(WearableItemType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?WearableItemType
    {
        return $this->type;
    }
}
