<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\UserEquipmentController;
use App\Repository\UserEquipmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserEquipmentRepository::class)]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/user_equipments/{id}/equip',
            controller: UserEquipmentController::class.'::equipItem',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'user_equipment_equip',
            deserialize: false
        ),
        new Post(
            uriTemplate: '/user_equipments/{id}/unequip',
            controller: UserEquipmentController::class.'::unequipItem',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'user_equipment_unequip',
            deserialize: false
        ),
    ]
)]
class UserEquipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'userEquipment', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'userEquipment', targetEntity: UserEquipmentSlot::class, cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private Collection $userEquipmentSlots;

    public function __construct()
    {
        $this->userEquipmentSlots = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getUserEquipmentSlots(): Collection
    {
        return $this->userEquipmentSlots;
    }

    public function addUserEquipmentSlot(UserEquipmentSlot $slot): static
    {
        if (!$this->userEquipmentSlots->contains($slot)) {
            $this->userEquipmentSlots->add($slot);
            $slot->setUserEquipment($this);
        }

        return $this;
    }

    public function removeUserEquipmentSlot(UserEquipmentSlot $slot): static
    {
        if ($this->userEquipmentSlots->removeElement($slot)) {
            if ($slot->getUserEquipment() === $this) {
                $slot->setUserEquipment(null);
            }
        }

        return $this;
    }
}
