<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserBooster;
use App\Enum\BoosterType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserBoosterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBooster::class);
    }

    public function findActiveBoosterByUserAndType(string $userId, BoosterType $boosterType): ?UserBooster
    {
        return $this->createQueryBuilder('ub')
            ->leftJoin('ub.boosterTemplate', 'bt')
            ->where('ub.user = :userId')
            ->andWhere('bt.type = :boosterType')
            ->andWhere('ub.expiresAt > :now')
            ->setParameter('userId', $userId)
            ->setParameter('boosterType', $boosterType)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findExpiredBoostersByUser(string $userId, \DateTime $now): array
    {
        return $this->createQueryBuilder('ub')
            ->leftJoin('ub.boosterTemplate', 'bt')
            ->addSelect('bt')
            ->where('ub.user = :userId')
            ->andWhere('ub.expiresAt <= :now')
            ->setParameter('userId', $userId)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    public function findActiveBoostersByUser(string $userId, \DateTime $now): array
    {
        return $this->createQueryBuilder('ub')
            ->leftJoin('ub.boosterTemplate', 'bt')
            ->addSelect('bt')
            ->where('ub.user = :userId')
            ->andWhere('ub.expiresAt > :now')
            ->setParameter('userId', $userId)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
}
