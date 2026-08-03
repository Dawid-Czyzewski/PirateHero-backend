<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserDungeonProgress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserDungeonProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDungeonProgress::class);
    }

    public function findOneForUserDungeon(User $user, string $dungeonId): ?UserDungeonProgress
    {
        return $this->findOneBy([
            'user' => $user,
            'dungeonId' => $dungeonId,
        ]);
    }

    /**
     * @return array<string, int> dungeonId => clearedStage
     */
    public function getProgressMapForUser(User $user): array
    {
        $entries = $this->findBy(['user' => $user]);
        $map = [];
        foreach ($entries as $entry) {
            $map[$entry->getDungeonId()] = $entry->getClearedStage();
        }

        return $map;
    }
}
