<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ShopBoosterCategory;
use App\Enum\ShopBoosterCurrency;
use App\Repository\ShopBoosterRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShopBoosterRepository::class)]
#[ORM\Table(name: 'shop_booster')]
#[ORM\UniqueConstraint(name: 'uniq_shop_booster_public_code', fields: ['publicCode'])]
class ShopBooster
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 16)]
    private string $publicCode;

    #[ORM\Column(enumType: ShopBoosterCategory::class)]
    private ShopBoosterCategory $category;

    #[ORM\Column(enumType: ShopBoosterCurrency::class)]
    private ShopBoosterCurrency $currency;

    #[ORM\Column]
    private int $price = 0;

    #[ORM\Column]
    private int $durationHours = 96;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\Column(type: 'text')]
    private string $effect = '';

    #[ORM\Column]
    private int $sortOrder = 0;

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

    public function getCategory(): ShopBoosterCategory
    {
        return $this->category;
    }

    public function setCategory(ShopBoosterCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getCurrency(): ShopBoosterCurrency
    {
        return $this->currency;
    }

    public function setCurrency(ShopBoosterCurrency $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getDurationHours(): int
    {
        return $this->durationHours;
    }

    public function setDurationHours(int $durationHours): static
    {
        $this->durationHours = $durationHours;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getEffect(): string
    {
        return $this->effect;
    }

    public function setEffect(string $effect): static
    {
        $this->effect = $effect;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }
}
