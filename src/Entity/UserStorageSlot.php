<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserStorageSlotRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserStorageSlotRepository::class)]
class UserStorageSlot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $slotNumber;

    #[ORM\ManyToOne(targetEntity: WearableItem::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['user:read'])]
    private ?WearableItem $item = null;

    #[ORM\ManyToOne(targetEntity: UserStorage::class, inversedBy: 'slots')]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserStorage $storage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlotNumber(): int
    {
        return $this->slotNumber;
    }

    public function setSlotNumber(int $slotNumber): self
    {
        $this->slotNumber = $slotNumber;

        return $this;
    }

    public function getItem(): ?WearableItem
    {
        return $this->item;
    }

    public function setItem(?WearableItem $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function getStorage(): ?UserStorage
    {
        return $this->storage;
    }

    public function setStorage(?UserStorage $storage): self
    {
        $this->storage = $storage;

        return $this;
    }
}
