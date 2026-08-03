<?php

declare(strict_types=1);

namespace App\Dungeon;

use App\Domain\Constants\DungeonConstants;
use App\Enum\DungeonId;


final class DungeonCatalog
{
    public const STAGES_PER_DUNGEON = 10;

    /**
     * @return list<array{
     *     id: string,
     *     reqLevel: int,
     *     enemyNameKey: string,
     *     baseHp: int,
     *     baseDmg: int,
     *     rewardsEnabled: bool,
     *     goldPerStage: int,
     *     expPerStage: int,
     *     completionGold: int,
     *     completionDiamonds: int,
     *     completionGrantsItem: bool,
     *     completionItemRarity: string
     * }>
     */
    public static function all(): array
    {
        return [
            self::definition(DungeonId::Krypta, 15, 'dungeonsPage.dungeons.krypta.enemy', 80, 10, true, 40, 8, 500, 10, true, 'RARE'),
            self::definition(DungeonId::Kraken, 25, 'dungeonsPage.dungeons.kraken.enemy', 120, 14, true, 70, 10, 700, 15, true, 'RARE'),
            self::definition(DungeonId::Forteca, 40, 'dungeonsPage.dungeons.forteca.enemy', 170, 18, true, 110, 12, 1000, 20, true, 'EPIC'),
            self::definition(DungeonId::Wulkan, 60, 'dungeonsPage.dungeons.wulkan.enemy', 230, 24, true, 138, 14, 1500, 30, true, 'EPIC'),
            self::definition(DungeonId::Palac, 80, 'dungeonsPage.dungeons.palac.enemy', 320, 32, true, 166, 16, 2500, 40, true, 'LEGENDARY'),
        ];
    }

    public static function get(DungeonId $id): ?array
    {
        foreach (self::all() as $dungeon) {
            if ($dungeon['id'] === $id->value) {
                return $dungeon;
            }
        }

        return null;
    }

    public static function seed(DungeonId $dungeonId, int $stage): int
    {
        $h = $stage * 997;
        $raw = $dungeonId->value;
        $len = strlen($raw);
        for ($i = 0; $i < $len; ++$i) {
            $h = ($h * 31 + ord($raw[$i])) & 0x7FFFFFFF;
        }

        return max(1, abs($h));
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     enemyNameKey: string,
     *     avatarId: string,
     *     level: int,
     *     famePoints: int,
     *     strength: int,
     *     agility: int,
     *     endurance: int,
     *     intelligence: int,
     *     luck: int
     * }
     */
    public static function buildOpponent(DungeonId $dungeonId, int $stage, string $displayName): array
    {
        $dungeon = self::get($dungeonId);
        if ($dungeon === null) {
            throw new \InvalidArgumentException('Unknown dungeon');
        }

        $enemyHp = (int) round($dungeon['baseHp'] * (1 + ($stage - 1) * DungeonConstants::STAGE_HP_SCALE));
        $enemyDmg = (int) round($dungeon['baseDmg'] * (1 + ($stage - 1) * DungeonConstants::STAGE_DAMAGE_SCALE));
        $endurance = max(1, (int) ceil($enemyHp / DungeonConstants::HP_TO_ENDURANCE_DIVISOR));
        $strength = max(DungeonConstants::MIN_STRENGTH, $enemyDmg);

        return [
            'id' => sprintf('dungeon-%s-s%d', $dungeonId->value, $stage),
            'name' => $displayName,
            'enemyNameKey' => $dungeon['enemyNameKey'],
            'avatarId' => 'captain',
            'level' => $dungeon['reqLevel'] + $stage - 1,
            'famePoints' => 0,
            'strength' => $strength,
            'agility' => max(DungeonConstants::MIN_AGILITY, (int) round($strength * DungeonConstants::AGILITY_FROM_STRENGTH_RATIO)),
            'endurance' => $endurance,
            'intelligence' => DungeonConstants::BASE_INTELLIGENCE + $stage,
            'luck' => DungeonConstants::BASE_LUCK + (int) floor($stage / 2),
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     reqLevel: int,
     *     enemyNameKey: string,
     *     baseHp: int,
     *     baseDmg: int,
     *     rewardsEnabled: bool,
     *     goldPerStage: int,
     *     expPerStage: int,
     *     completionGold: int,
     *     completionDiamonds: int,
     *     completionGrantsItem: bool,
     *     completionItemRarity: string
     * }
     */
    private static function definition(
        DungeonId $id,
        int $reqLevel,
        string $enemyNameKey,
        int $baseHp,
        int $baseDmg,
        bool $rewardsEnabled,
        int $goldPerStage,
        int $expPerStage,
        int $completionGold,
        int $completionDiamonds,
        bool $completionGrantsItem,
        string $completionItemRarity,
    ): array {
        return [
            'id' => $id->value,
            'reqLevel' => $reqLevel,
            'enemyNameKey' => $enemyNameKey,
            'baseHp' => $baseHp,
            'baseDmg' => $baseDmg,
            'rewardsEnabled' => $rewardsEnabled,
            'goldPerStage' => $goldPerStage,
            'expPerStage' => $expPerStage,
            'completionGold' => $completionGold,
            'completionDiamonds' => $completionDiamonds,
            'completionGrantsItem' => $completionGrantsItem,
            'completionItemRarity' => $completionItemRarity,
        ];
    }

    /**
     * @param array<string, int> $dungeonProgress
     */
    public static function isDungeonCompleted(array $dungeonProgress, string $dungeonId): bool
    {
        if ($dungeonId === '') {
            return false;
        }

        return ($dungeonProgress[$dungeonId] ?? 0) >= self::STAGES_PER_DUNGEON;
    }

    /**
     * @param array<string, int> $dungeonProgress
     */
    public static function countCompletedDungeons(array $dungeonProgress): int
    {
        $count = 0;
        foreach (self::all() as $dungeon) {
            if (self::isDungeonCompleted($dungeonProgress, $dungeon['id'])) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @param array<string, int> $dungeonProgress
     */
    public static function areAllDungeonsCompleted(array $dungeonProgress): bool
    {
        $all = self::all();

        return $all !== [] && self::countCompletedDungeons($dungeonProgress) === \count($all);
    }
}
