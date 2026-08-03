<?php

declare(strict_types=1);

namespace App\Bestiary;

use App\Enum\DungeonId;

final class BestiaryCatalog
{
    private const DUNGEON_IDS = [
        DungeonId::Krypta,
        DungeonId::Kraken,
        DungeonId::Forteca,
        DungeonId::Wulkan,
        DungeonId::Palac,
    ];

    /**
     * @return list<array{
     *     enemyId: string,
     *     dungeonId: string,
     *     stage: int,
     *     nameKey: string,
     *     loreKey: string
     * }>
     */
    public static function entries(): array
    {
        $entries = [];
        foreach (self::DUNGEON_IDS as $dungeonId) {
            for ($stage = 1; $stage <= 10; ++$stage) {
                $stageKey = sprintf('stage%02d', $stage);
                $entries[] = [
                    'enemyId' => sprintf('%s-s%d', $dungeonId->value, $stage),
                    'dungeonId' => $dungeonId->value,
                    'stage' => $stage,
                    'nameKey' => sprintf('dungeonsPage.dungeons.%s.enemies.%s', $dungeonId->value, $stageKey),
                    'loreKey' => sprintf('bestiaryPage.enemies.%s.%s.lore', $dungeonId->value, $stageKey),
                ];
            }
        }

        return $entries;
    }

    /**
     * @return array{enemyId: string, dungeonId: string, stage: int, nameKey: string, loreKey: string}|null
     */
    public static function find(string $dungeonId, int $stage): ?array
    {
        foreach (self::entries() as $entry) {
            if ($entry['dungeonId'] === $dungeonId && $entry['stage'] === $stage) {
                return $entry;
            }
        }

        return null;
    }
}
