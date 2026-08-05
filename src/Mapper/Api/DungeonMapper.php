<?php

declare(strict_types=1);

namespace App\Mapper\Api;

use App\Dto\Api\Dungeon\DungeonBattleLogEntryDto;
use App\Dto\Api\Dungeon\DungeonFightStageResponse;
use App\Dto\Api\Dungeon\DungeonOpponentDto;
use App\Dto\Api\Dungeon\DungeonPlayerStatsDto;
use App\Dto\Api\Dungeon\DungeonProgressResponse;
use App\Dto\Api\Dungeon\DungeonStageRewardsDto;

final readonly class DungeonMapper
{
    /**
     * @param array<string, int> $progress
     * @param array{level: int, strength: int, agility: int, endurance: int, intelligence: int, luck: int} $playerStats
     */
    public static function progressResponse(
        array $progress,
        array $playerStats,
        ?string $cooldownUntil = null,
        int $cooldownSecondsRemaining = 0,
    ): DungeonProgressResponse {
        return new DungeonProgressResponse(
            progress: $progress,
            playerStats: self::playerStats($playerStats),
            cooldownUntil: $cooldownUntil,
            cooldownSecondsRemaining: $cooldownSecondsRemaining,
        );
    }

    /**
     * @param array{level: int, strength: int, agility: int, endurance: int, intelligence: int, luck: int} $stats
     */
    public static function playerStats(array $stats): DungeonPlayerStatsDto
    {
        return new DungeonPlayerStatsDto(
            level: $stats['level'],
            strength: $stats['strength'],
            agility: $stats['agility'],
            endurance: $stats['endurance'],
            intelligence: $stats['intelligence'],
            luck: $stats['luck'],
        );
    }

    /**
     * @param array{
     *     won: bool,
     *     logs: list<array{attackerIsPlayer: bool, damage: int, critical: bool}>,
     *     playerMaxHp: int,
     *     opponentMaxHp: int,
     *     fameEarned: int,
     *     famePointsChange: int,
     *     progress: array<string, int>,
     *     opponent: array{
     *         id: string,
     *         name: string,
     *         enemyNameKey: string,
     *         avatarId: string,
     *         level: int,
     *         famePoints: int,
     *         strength: int,
     *         agility: int,
     *         endurance: int,
     *         intelligence: int,
     *         luck: int
     *     },
     *     rewards: array{gold: int, exp: int},
     *     completionReward: array{gold: int, diamonds: int, item: array<string, mixed>|null}|null,
     *     dungeonCompleted: bool,
     *     rewardItem: array<string, mixed>|null,
     *     updatedUser: array{
     *         gold: int,
     *         diamonds: int,
     *         experiencePoints: int,
     *         freeSkillPointsAvailable: int,
     *         level: array{name: string, expToNextLevel: int},
     *         storage?: array{id: int|string, slots: list<array<string, mixed>>}
     *     }|null,
     *     cooldownUntil?: string|null,
     *     cooldownSecondsRemaining?: int
     * } $battle
     */
    public static function fightStageResponse(array $battle): DungeonFightStageResponse
    {
        $logs = [];
        foreach ($battle['logs'] as $log) {
            $logs[] = new DungeonBattleLogEntryDto(
                attackerIsPlayer: (bool) $log['attackerIsPlayer'],
                damage: (int) $log['damage'],
                critical: (bool) $log['critical'],
            );
        }

        $opponent = $battle['opponent'];
        $rewards = $battle['rewards'];

        return new DungeonFightStageResponse(
            won: (bool) $battle['won'],
            logs: $logs,
            playerMaxHp: (int) $battle['playerMaxHp'],
            opponentMaxHp: (int) $battle['opponentMaxHp'],
            fameEarned: (int) $battle['fameEarned'],
            famePointsChange: (int) $battle['famePointsChange'],
            progress: $battle['progress'],
            opponent: new DungeonOpponentDto(
                id: (string) $opponent['id'],
                name: (string) $opponent['name'],
                enemyNameKey: (string) $opponent['enemyNameKey'],
                avatarId: (string) $opponent['avatarId'],
                level: (int) $opponent['level'],
                famePoints: (int) $opponent['famePoints'],
                strength: (int) $opponent['strength'],
                agility: (int) $opponent['agility'],
                endurance: (int) $opponent['endurance'],
                intelligence: (int) $opponent['intelligence'],
                luck: (int) $opponent['luck'],
            ),
            rewards: new DungeonStageRewardsDto(
                gold: (int) $rewards['gold'],
                exp: (int) $rewards['exp'],
            ),
            completionReward: $battle['completionReward'],
            dungeonCompleted: (bool) $battle['dungeonCompleted'],
            rewardItem: $battle['rewardItem'],
            updatedUser: $battle['updatedUser'],
            cooldownUntil: $battle['cooldownUntil'] ?? null,
            cooldownSecondsRemaining: (int) ($battle['cooldownSecondsRemaining'] ?? 0),
        );
    }
}
