<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserCapacitiesRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserCapacitiesRepository::class)]
class UserCapacities
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'userCapacities', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[Groups(['user:read'])]
    #[ORM\Column]
    private ?int $energyPoints = null;

    #[Groups(['user:read'])]
    #[ORM\Column]
    private ?int $trainingPoints = null;

    #[Groups(['user:read'])]
    #[ORM\Column]
    private ?int $fightPoints = null;

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

    public function getEnergyPoints(): ?int
    {
        return $this->energyPoints;
    }

    public function setEnergyPoints(int $energyPoints): static
    {
        $this->energyPoints = $energyPoints;

        return $this;
    }

    public function getTrainingPoints(): ?int
    {
        return $this->trainingPoints;
    }

    public function setTrainingPoints(int $trainingPoints): static
    {
        $this->trainingPoints = $trainingPoints;

        return $this;
    }

    public function getFightPoints(): ?int
    {
        return $this->fightPoints;
    }

    public function setFightPoints(int $fightPoints): static
    {
        $this->fightPoints = $fightPoints;

        return $this;
    }
}
