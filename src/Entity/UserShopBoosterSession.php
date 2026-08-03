<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserShopBoosterSessionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserShopBoosterSessionRepository::class)]
#[ORM\Table(name: 'user_shop_booster_session')]
#[ORM\Index(name: 'idx_user_shop_booster_session_user_expires', fields: ['user', 'expiresAt'])]
class UserShopBoosterSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: ShopBooster::class)]
    #[ORM\JoinColumn(name: 'shop_booster_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?ShopBooster $shopBooster = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $expiresAt = null;

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

    public function getShopBooster(): ?ShopBooster
    {
        return $this->shopBooster;
    }

    public function setShopBooster(?ShopBooster $shopBooster): static
    {
        $this->shopBooster = $shopBooster;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }
}
