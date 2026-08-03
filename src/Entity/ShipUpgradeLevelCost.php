<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ShipUpgradeLevelCostRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: ShipUpgradeLevelCostRepository::class)]
#[ORM\Table(name: 'ship_upgrade_level_cost')]
class ShipUpgradeLevelCost
{
    #[ORM\Id]
    #[ORM\Column(name: 'upgrade_type', length: 20)]
    private string $upgradeType;

    #[ORM\Id]
    #[ORM\Column(name: 'target_level', type: Types::INTEGER)]
    private int $targetLevel;

    #[ORM\Column(type: Types::INTEGER)]
    private int $gold = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $diamonds = 0;

    public function __construct(string $upgradeType, int $targetLevel)
    {
        $this->upgradeType = $upgradeType;
        $this->targetLevel = $targetLevel;
    }

    public function getUpgradeType(): string
    {
        return $this->upgradeType;
    }

    public function getTargetLevel(): int
    {
        return $this->targetLevel;
    }

    public function getGold(): int
    {
        return $this->gold;
    }

    public function getDiamonds(): int
    {
        return $this->diamonds;
    }
}
