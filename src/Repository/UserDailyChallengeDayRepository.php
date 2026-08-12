<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserDailyChallengeDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserDailyChallengeDay>
 */
class UserDailyChallengeDayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDailyChallengeDay::class);
    }

    public function findOneForUserDate(User $user, \DateTimeImmutable $date): ?UserDailyChallengeDay
    {
        return $this->findOneBy([
            'user' => $user,
            'challengeDate' => $date,
        ]);
    }
}
