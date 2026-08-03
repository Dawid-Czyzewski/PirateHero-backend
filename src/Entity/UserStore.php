<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Controller\UserStoreController;
use App\Repository\UserStoreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserStoreRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/user-store/by-user/{id}',
            controller: UserStoreController::class.'::getByUserId',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_store_by_user',
            normalizationContext: ['groups' => ['user:read']]
        ),
        new Post(
            uriTemplate: '/user-store/buy-item',
            controller: UserStoreController::class.'::buyItem',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            normalizationContext: ['groups' => ['user:read']],
            name: 'buy_item'
        ),
        new Post(
            uriTemplate: '/user-store/sell-item',
            controller: UserStoreController::class.'::sellItem',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            normalizationContext: ['groups' => ['user:read']]
        ),
        new Post(
            uriTemplate: '/user-store/refresh',
            controller: UserStoreController::class.'::refreshStore',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            normalizationContext: ['groups' => ['user:read']]
        ),
    ]
)]
class UserStore
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?bool $isFreeRefreshAvailable = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $refreshCost = null;

    #[ORM\OneToOne(inversedBy: 'userStore', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'store', targetEntity: StoreSlot::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['user:read'])]
    private Collection $storeSlots;

    public function __construct()
    {
        $this->storeSlots = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIsFreeRefreshAvailable(): ?bool
    {
        return $this->isFreeRefreshAvailable;
    }

    public function setIsFreeRefreshAvailable(bool $isFreeRefreshAvailable): static
    {
        $this->isFreeRefreshAvailable = $isFreeRefreshAvailable;

        return $this;
    }

    public function getRefreshCost(): ?int
    {
        return $this->refreshCost;
    }

    public function setRefreshCost(int $refreshCost): static
    {
        $this->refreshCost = $refreshCost;

        return $this;
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

    public function getStoreSlots(): Collection
    {
        return $this->storeSlots;
    }

    public function addStoreSlot(StoreSlot $storeSlot): static
    {
        if (!$this->storeSlots->contains($storeSlot)) {
            $this->storeSlots->add($storeSlot);
            $storeSlot->setStore($this);
        }

        return $this;
    }

    public function removeStoreSlot(StoreSlot $storeSlot): static
    {
        if ($this->storeSlots->removeElement($storeSlot)) {
            if ($storeSlot->getStore() === $this) {
                $storeSlot->setStore(null);
            }
        }

        return $this;
    }
}
