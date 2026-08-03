<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\StorageController;
use App\Repository\UserStorageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserStorageRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['user:read']],
    operations: [
        new Post(
            uriTemplate: '/storage/{id}/move-item/{fromSlot}/{toSlot}',
            controller: StorageController::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'storage_move_item',
        ),
    ]
)]
class UserStorage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'storage', targetEntity: UserStorageSlot::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['user:read'])]
    private Collection $slots;

    #[ORM\OneToOne(inversedBy: 'storage', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct()
    {
        $this->slots = new ArrayCollection();
    }

    public function getSlots(): Collection
    {
        return $this->slots;
    }

    public function addSlot(UserStorageSlot $slot): static
    {
        if (!$this->slots->contains($slot)) {
            $this->slots[] = $slot;
            $slot->setStorage($this);
        }

        return $this;
    }

    public function removeSlot(UserStorageSlot $slot): static
    {
        if ($this->slots->removeElement($slot)) {
            if ($slot->getStorage() === $this) {
                $slot->setStorage(null);
            }
        }

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

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
