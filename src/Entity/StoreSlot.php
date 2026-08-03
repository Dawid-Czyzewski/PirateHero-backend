<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\StoreSlotRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: StoreSlotRepository::class)]
#[ApiResource]
class StoreSlot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $slotNumber = null;

    #[ORM\ManyToOne(targetEntity: WearableItem::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['user:read'])]
    private ?WearableItem $item = null;

    #[ORM\ManyToOne(inversedBy: 'storeSlots')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['user:read'])]
    private ?UserStore $store = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlotNumber(): ?int
    {
        return $this->slotNumber;
    }

    public function setSlotNumber(int $slotNumber): static
    {
        $this->slotNumber = $slotNumber;

        return $this;
    }

    public function getItem(): ?WearableItem
    {
        return $this->item;
    }

    public function setItem(?WearableItem $item): static
    {
        $this->item = $item;

        return $this;
    }

    public function getStore(): ?UserStore
    {
        return $this->store;
    }

    public function setStore(?UserStore $store): static
    {
        $this->store = $store;

        return $this;
    }
}
