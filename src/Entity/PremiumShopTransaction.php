<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PremiumShopTransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PremiumShopTransactionRepository::class)]
#[ORM\Table(name: 'premium_shop_transaction')]
class PremiumShopTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 32)]
    private string $packId = '';

    #[ORM\Column]
    private int $diamonds = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $pricePln = '0.00';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $purchasedAt = null;

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

    public function getPackId(): string
    {
        return $this->packId;
    }

    public function setPackId(string $packId): static
    {
        $this->packId = $packId;

        return $this;
    }

    public function getDiamonds(): int
    {
        return $this->diamonds;
    }

    public function setDiamonds(int $diamonds): static
    {
        $this->diamonds = $diamonds;

        return $this;
    }

    public function getPricePln(): string
    {
        return $this->pricePln;
    }

    public function setPricePln(string $pricePln): static
    {
        $this->pricePln = $pricePln;

        return $this;
    }

    public function getPurchasedAt(): ?\DateTimeImmutable
    {
        return $this->purchasedAt;
    }

    public function setPurchasedAt(\DateTimeImmutable $purchasedAt): static
    {
        $this->purchasedAt = $purchasedAt;

        return $this;
    }
}
