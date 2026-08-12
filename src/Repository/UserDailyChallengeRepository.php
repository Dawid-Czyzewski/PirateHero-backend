<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserDailyChallenge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserDailyChallenge>
 */
class UserDailyChallengeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDailyChallenge::class);
    }

    /**
     * @return list<UserDailyChallenge>
     */
    public function findForUserDate(User $user, \DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.challengeDate = :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date)
            ->orderBy('c.slot', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForUserDateSlot(User $user, \DateTimeImmutable $date, int $slot): ?UserDailyChallenge
    {
        return $this->findOneBy([
            'user' => $user,
            'challengeDate' => $date,
            'slot' => $slot,
        ]);
    }
}
