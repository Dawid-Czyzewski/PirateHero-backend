<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserBaseStatisticsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserBaseStatisticsRepository::class)]
class UserBaseStatistics
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'endurance_points')]
    #[Groups(['user:read'])]
    private ?int $endurance = null;

    #[ORM\Column(name: 'strength_points')]
    #[Groups(['user:read'])]
    private ?int $strength = null;

    #[ORM\Column(name: 'agility_points')]
    #[Groups(['user:read'])]
    private ?int $agility = null;

    #[ORM\Column(name: 'intelligence_points')]
    #[Groups(['user:read'])]
    private ?int $intelligence = null;

    #[ORM\Column(name: 'luck_points')]
    #[Groups(['user:read'])]
    private ?int $luck = null;

    #[ORM\OneToOne(inversedBy: 'userBaseStatistics', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEndurance(): ?int
    {
        return $this->endurance;
    }

    public function setEndurance(int $endurance): static
    {
        $this->endurance = $endurance;

        return $this;
    }

    public function getStrength(): ?int
    {
        return $this->strength;
    }

    public function setStrength(int $strength): static
    {
        $this->strength = $strength;

        return $this;
    }

    public function getAgility(): ?int
    {
        return $this->agility;
    }

    public function setAgility(int $agility): static
    {
        $this->agility = $agility;

        return $this;
    }

    public function getIntelligence(): ?int
    {
        return $this->intelligence;
    }

    public function setIntelligence(int $intelligence): static
    {
        $this->intelligence = $intelligence;

        return $this;
    }

    public function getLuck(): ?int
    {
        return $this->luck;
    }

    public function setLuck(int $luck): static
    {
        $this->luck = $luck;

        return $this;
    }

    public function getHealthPoints(): ?int
    {
        return $this->getEndurance();
    }

    public function setHealthPoints(int $healthPoints): static
    {
        return $this->setEndurance($healthPoints);
    }

    public function getStrongPoints(): ?int
    {
        return $this->getStrength();
    }

    public function setStrongPoints(int $strongPoints): static
    {
        return $this->setStrength($strongPoints);
    }

    public function getAgilityPoints(): ?int
    {
        return $this->getAgility();
    }

    public function setAgilityPoints(int $agilityPoints): static
    {
        return $this->setAgility($agilityPoints);
    }

    public function getCriticalChancePoints(): ?int
    {
        return $this->getIntelligence();
    }

    public function setCriticalChancePoints(int $criticalChancePoints): static
    {
        return $this->setIntelligence($criticalChancePoints);
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
