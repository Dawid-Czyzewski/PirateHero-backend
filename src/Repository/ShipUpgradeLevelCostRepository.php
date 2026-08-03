<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ShipUpgradeLevelCost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ShipUpgradeLevelCostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShipUpgradeLevelCost::class);
    }
}
