<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserWeeklyArenaFame;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserWeeklyArenaFame>
 */
class UserWeeklyArenaFameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserWeeklyArenaFame::class);
    }

    public function findOneForUserWeek(User $user, \DateTimeImmutable $weekStart): ?UserWeeklyArenaFame
    {
        return $this->findOneBy([
            'user' => $user,
            'weekStart' => $weekStart,
        ]);
    }
}
