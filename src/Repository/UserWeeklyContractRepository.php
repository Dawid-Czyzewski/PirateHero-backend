<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserWeeklyContract;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserWeeklyContract>
 */
class UserWeeklyContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserWeeklyContract::class);
    }

    public function findOneForUserWeek(User $user, \DateTimeImmutable $weekStart): ?UserWeeklyContract
    {
        return $this->findOneBy([
            'user' => $user,
            'weekStart' => $weekStart,
        ]);
    }
}
