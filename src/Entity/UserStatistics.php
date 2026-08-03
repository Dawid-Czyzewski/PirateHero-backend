<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserStatisticsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserStatisticsRepository::class)]
#[ORM\Table(name: 'user_statistics')]
class UserStatistics
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'userStatistics', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $goldSpent = 0;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $fightsWon = 0;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $trainingsCompleted = 0;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $worksCompleted = 0;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $fightsLost = 0;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $levelsReached = 1;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $itemsCollected = 0;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $rareItemsCollected = 0;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $equipmentFullReached = 0;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $rareEquipmentFullReached = 0;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $legendaryItemsCollected = 0;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $epicItemsCollected = 0;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $epicEquipmentFullReached = 0;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $legendaryEquipmentFullReached = 0;

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

    public function getGoldSpent(): int
    {
        return $this->goldSpent;
    }

    public function setGoldSpent(int $goldSpent): static
    {
        $this->goldSpent = $goldSpent;

        return $this;
    }

    public function addGoldSpent(int $amount): static
    {
        $this->goldSpent += $amount;

        return $this;
    }

    public function getFightsWon(): int
    {
        return $this->fightsWon;
    }

    public function setFightsWon(int $fightsWon): static
    {
        $this->fightsWon = $fightsWon;

        return $this;
    }

    public function incrementFightsWon(): static
    {
        ++$this->fightsWon;

        return $this;
    }

    public function getTrainingsCompleted(): int
    {
        return $this->trainingsCompleted;
    }

    public function setTrainingsCompleted(int $trainingsCompleted): static
    {
        $this->trainingsCompleted = $trainingsCompleted;

        return $this;
    }

    public function incrementTrainingsCompleted(int $amount = 1): static
    {
        $this->trainingsCompleted += $amount;

        return $this;
    }

    public function getWorksCompleted(): int
    {
        return $this->worksCompleted;
    }

    public function setWorksCompleted(int $worksCompleted): static
    {
        $this->worksCompleted = $worksCompleted;

        return $this;
    }

    public function incrementWorksCompleted(int $amount = 1): static
    {
        $this->worksCompleted += $amount;

        return $this;
    }

    public function getFightsLost(): int
    {
        return $this->fightsLost;
    }

    public function setFightsLost(int $fightsLost): static
    {
        $this->fightsLost = $fightsLost;

        return $this;
    }

    public function incrementFightsLost(): static
    {
        ++$this->fightsLost;

        return $this;
    }

    public function getLevelsReached(): int
    {
        return $this->levelsReached;
    }

    public function setLevelsReached(int $levelsReached): static
    {
        $this->levelsReached = $levelsReached;

        return $this;
    }

    public function updateLevelsReached(int $levelNumber): static
    {
        if ($levelNumber > $this->levelsReached) {
            $this->levelsReached = $levelNumber;
        }

        return $this;
    }

    public function getItemsCollected(): int
    {
        return $this->itemsCollected;
    }

    public function setItemsCollected(int $itemsCollected): static
    {
        $this->itemsCollected = $itemsCollected;

        return $this;
    }

    public function incrementItemsCollected(): static
    {
        ++$this->itemsCollected;

        return $this;
    }

    public function getRareItemsCollected(): int
    {
        return $this->rareItemsCollected;
    }

    public function setRareItemsCollected(int $rareItemsCollected): static
    {
        $this->rareItemsCollected = $rareItemsCollected;

        return $this;
    }

    public function incrementRareItemsCollected(): static
    {
        ++$this->rareItemsCollected;

        return $this;
    }

    public function getEquipmentFullReached(): int
    {
        return $this->equipmentFullReached;
    }

    public function setEquipmentFullReached(int $equipmentFullReached): static
    {
        $this->equipmentFullReached = $equipmentFullReached;

        return $this;
    }

    public function markEquipmentFullReached(): static
    {
        $this->equipmentFullReached = 1;

        return $this;
    }

    public function getRareEquipmentFullReached(): int
    {
        return $this->rareEquipmentFullReached;
    }

    public function setRareEquipmentFullReached(int $rareEquipmentFullReached): static
    {
        $this->rareEquipmentFullReached = $rareEquipmentFullReached;

        return $this;
    }

    public function markRareEquipmentFullReached(): static
    {
        $this->rareEquipmentFullReached = 1;

        return $this;
    }

    public function getLegendaryItemsCollected(): int
    {
        return $this->legendaryItemsCollected;
    }

    public function setLegendaryItemsCollected(int $legendaryItemsCollected): static
    {
        $this->legendaryItemsCollected = $legendaryItemsCollected;

        return $this;
    }

    public function incrementLegendaryItemsCollected(): static
    {
        ++$this->legendaryItemsCollected;

        return $this;
    }

    public function getEpicItemsCollected(): int
    {
        return $this->epicItemsCollected;
    }

    public function setEpicItemsCollected(int $epicItemsCollected): static
    {
        $this->epicItemsCollected = $epicItemsCollected;

        return $this;
    }

    public function incrementEpicItemsCollected(): static
    {
        ++$this->epicItemsCollected;

        return $this;
    }

    public function getEpicEquipmentFullReached(): int
    {
        return $this->epicEquipmentFullReached;
    }

    public function setEpicEquipmentFullReached(int $epicEquipmentFullReached): static
    {
        $this->epicEquipmentFullReached = $epicEquipmentFullReached;

        return $this;
    }

    public function markEpicEquipmentFullReached(): static
    {
        $this->epicEquipmentFullReached = 1;

        return $this;
    }

    public function getLegendaryEquipmentFullReached(): int
    {
        return $this->legendaryEquipmentFullReached;
    }

    public function setLegendaryEquipmentFullReached(int $legendaryEquipmentFullReached): static
    {
        $this->legendaryEquipmentFullReached = $legendaryEquipmentFullReached;

        return $this;
    }

    public function markLegendaryEquipmentFullReached(): static
    {
        $this->legendaryEquipmentFullReached = 1;

        return $this;
    }
}
