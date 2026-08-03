<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ShipJoinRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ShipJoinRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShipJoinRequest::class);
    }

    public function findPendingByShip($ship): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.ship = :ship')
            ->andWhere('r.approved IS NULL')
            ->setParameter('ship', $ship)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
