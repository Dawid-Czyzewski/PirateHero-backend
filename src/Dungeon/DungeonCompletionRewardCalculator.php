<?php

declare(strict_types=1);

namespace App\Dungeon;

use App\Enum\DungeonId;

final class DungeonCompletionRewardCalculator
{
    public function forDungeon(DungeonId $dungeonId): DungeonCompletionReward
    {
        $dungeon = DungeonCatalog::get($dungeonId);
        if ($dungeon === null) {
            return new DungeonCompletionReward(0, 0, false);
        }

        return new DungeonCompletionReward(
            gold: (int) ($dungeon['completionGold'] ?? 0),
            diamonds: (int) ($dungeon['completionDiamonds'] ?? 0),
            grantsItem: (bool) ($dungeon['completionGrantsItem'] ?? false),
            itemRarity: (string) ($dungeon['completionItemRarity'] ?? 'RARE'),
        );
    }
}
