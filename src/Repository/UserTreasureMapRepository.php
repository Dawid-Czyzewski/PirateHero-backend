<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserTreasureMap;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserTreasureMap>
 */
class UserTreasureMapRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserTreasureMap::class);
    }

    public function findOneForUserWeek(User $user, \DateTimeImmutable $weekStart): ?UserTreasureMap
    {
        return $this->findOneBy([
            'user' => $user,
            'weekStart' => $weekStart,
        ]);
    }
}
