<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserDungeonProgress;
use App\Enum\DungeonDifficulty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserDungeonProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDungeonProgress::class);
    }

    public function findOneForUserDungeon(
        User $user,
        string $dungeonId,
        string $difficulty = DungeonDifficulty::Normal->value,
    ): ?UserDungeonProgress {
        return $this->findOneBy([
            'user' => $user,
            'dungeonId' => $dungeonId,
            'difficulty' => $difficulty,
        ]);
    }

    /**
     * Normal difficulty only — used by quests/titles.
     *
     * @return array<string, int> dungeonId => clearedStage
     */
    public function getProgressMapForUser(User $user): array
    {
        return $this->getProgressMapForUserByDifficulty($user, DungeonDifficulty::Normal->value);
    }

    /**
     * @return array<string, int> dungeonId => clearedStage
     */
    public function getProgressMapForUserByDifficulty(User $user, string $difficulty): array
    {
        $entries = $this->findBy(['user' => $user, 'difficulty' => $difficulty]);
        $map = [];
        foreach ($entries as $entry) {
            $map[$entry->getDungeonId()] = $entry->getClearedStage();
        }

        return $map;
    }

    /**
     * @return array{normal: array<string, int>, hard: array<string, int>}
     */
    public function getProgressByDifficultyForUser(User $user): array
    {
        return [
            'normal' => $this->getProgressMapForUserByDifficulty($user, DungeonDifficulty::Normal->value),
            'hard' => $this->getProgressMapForUserByDifficulty($user, DungeonDifficulty::Hard->value),
        ];
    }
}
