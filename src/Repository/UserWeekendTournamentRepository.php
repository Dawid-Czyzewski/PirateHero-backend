<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserWeekendTournament;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserWeekendTournament>
 */
class UserWeekendTournamentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserWeekendTournament::class);
    }

    public function findOneForUserEvent(User $user, \DateTimeImmutable $eventStart): ?UserWeekendTournament
    {
        return $this->findOneBy([
            'user' => $user,
            'eventStart' => $eventStart,
        ]);
    }

    /**
     * @return list<UserWeekendTournament>
     */
    public function findTopForEvent(\DateTimeImmutable $eventStart, int $limit = 10): array
    {
        /** @var list<UserWeekendTournament> $rows */
        $rows = $this->createQueryBuilder('t')
            ->andWhere('t.eventStart = :eventStart')
            ->andWhere('t.points > 0')
            ->setParameter('eventStart', $eventStart)
            ->orderBy('t.points', 'DESC')
            ->addOrderBy('t.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $rows;
    }

    public function countPlayersWithMorePoints(\DateTimeImmutable $eventStart, int $points, int $id): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.eventStart = :eventStart')
            ->andWhere('(t.points > :points) OR (t.points = :points AND t.id < :id)')
            ->setParameter('eventStart', $eventStart)
            ->setParameter('points', $points)
            ->setParameter('id', $id)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
