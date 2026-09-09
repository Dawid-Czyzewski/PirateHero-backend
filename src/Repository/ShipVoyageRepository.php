<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Ship;
use App\Entity\ShipVoyage;
use App\Enum\ShipVoyageStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShipVoyage>
 */
class ShipVoyageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShipVoyage::class);
    }

    public function findActiveForShip(Ship $ship): ?ShipVoyage
    {
        return $this->findOneBy([
            'ship' => $ship,
            'status' => ShipVoyageStatus::Active->value,
        ]);
    }
}
