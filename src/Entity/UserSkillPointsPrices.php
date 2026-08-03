<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserSkillPointsPricesRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserSkillPointsPricesRepository::class)]
class UserSkillPointsPrices
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'endurance_points_price')]
    #[Groups(['user:read'])]
    private ?int $endurancePointsPrice = null;

    #[ORM\Column(name: 'strong_points_price')]
    #[Groups(['user:read'])]
    private ?int $strengthPointsPrice = null;

    #[ORM\Column(name: 'agility_points_price')]
    #[Groups(['user:read'])]
    private ?int $agilityPointsPrice = null;

    #[ORM\Column(name: 'intelligence_points_price')]
    #[Groups(['user:read'])]
    private ?int $intelligencePointsPrice = null;

    #[ORM\Column(name: 'luck_points_price')]
    #[Groups(['user:read'])]
    private ?int $luckPointsPrice = null;

    #[ORM\OneToOne(inversedBy: 'userSkillPointsPrices', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEndurancePointsPrice(): ?int
    {
        return $this->endurancePointsPrice;
    }

    public function setEndurancePointsPrice(int $endurancePointsPrice): static
    {
        $this->endurancePointsPrice = $endurancePointsPrice;

        return $this;
    }

    public function getStrengthPointsPrice(): ?int
    {
        return $this->strengthPointsPrice;
    }

    public function setStrengthPointsPrice(int $strengthPointsPrice): static
    {
        $this->strengthPointsPrice = $strengthPointsPrice;

        return $this;
    }

    public function getAgilityPointsPrice(): ?int
    {
        return $this->agilityPointsPrice;
    }

    public function setAgilityPointsPrice(int $agilityPointsPrice): static
    {
        $this->agilityPointsPrice = $agilityPointsPrice;

        return $this;
    }

    public function getIntelligencePointsPrice(): ?int
    {
        return $this->intelligencePointsPrice;
    }

    public function setIntelligencePointsPrice(int $intelligencePointsPrice): static
    {
        $this->intelligencePointsPrice = $intelligencePointsPrice;

        return $this;
    }

    public function getLuckPointsPrice(): ?int
    {
        return $this->luckPointsPrice;
    }

    public function setLuckPointsPrice(int $luckPointsPrice): static
    {
        $this->luckPointsPrice = $luckPointsPrice;

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

    public function getStrongPointsPrice(): ?int
    {
        return $this->getStrengthPointsPrice();
    }

    public function setStrongPointsPrice(int $strongPointsPrice): static
    {
        return $this->setStrengthPointsPrice($strongPointsPrice);
    }

    public function getHealthPointsPrice(): ?int
    {
        return $this->getEndurancePointsPrice();
    }

    public function setHealthPointsPrice(int $healthPointsPrice): static
    {
        return $this->setEndurancePointsPrice($healthPointsPrice);
    }

    public function getCriticalChancePointsPrice(): ?int
    {
        return $this->getIntelligencePointsPrice();
    }

    public function setCriticalChancePointsPrice(int $criticalChancePointsPrice): static
    {
        return $this->setIntelligencePointsPrice($criticalChancePointsPrice);
    }
}
