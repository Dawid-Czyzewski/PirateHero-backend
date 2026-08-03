<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserBestiaryEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserBestiaryEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBestiaryEntry::class);
    }

    public function findOneForUserDungeonStage(User $user, string $dungeonId, int $stage): ?UserBestiaryEntry
    {
        return $this->findOneBy([
            'user' => $user,
            'dungeonId' => $dungeonId,
            'stage' => $stage,
        ]);
    }

    /**
     * @return array<string, UserBestiaryEntry> keyed by enemyId e.g. krypta-s3
     */
    public function getDiscoveredMapForUser(User $user): array
    {
        $entries = $this->findBy(['user' => $user]);
        $map = [];
        foreach ($entries as $entry) {
            $enemyId = sprintf('%s-s%d', $entry->getDungeonId(), $entry->getStage());
            $map[$enemyId] = $entry;
        }

        return $map;
    }

    public function countForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('ube')
            ->select('COUNT(ube.id)')
            ->where('ube.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
