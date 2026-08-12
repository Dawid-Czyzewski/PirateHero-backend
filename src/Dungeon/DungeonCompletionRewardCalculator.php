<?php

declare(strict_types=1);

namespace App\Dungeon;

use App\Domain\Constants\DungeonConstants;
use App\Enum\DungeonDifficulty;
use App\Enum\DungeonId;

final class DungeonCompletionRewardCalculator
{
    public function forDungeon(
        DungeonId $dungeonId,
        DungeonDifficulty $difficulty = DungeonDifficulty::Normal,
    ): DungeonCompletionReward {
        $dungeon = DungeonCatalog::get($dungeonId);
        if ($dungeon === null) {
            return new DungeonCompletionReward(0, 0, false);
        }

        $mult = $difficulty === DungeonDifficulty::Hard
            ? DungeonConstants::HARD_COMPLETION_REWARD_MULTIPLIER
            : 1.0;

        return new DungeonCompletionReward(
            gold: (int) round(((int) ($dungeon['completionGold'] ?? 0)) * $mult),
            diamonds: (int) round(((int) ($dungeon['completionDiamonds'] ?? 0)) * $mult),
            grantsItem: (bool) ($dungeon['completionGrantsItem'] ?? false),
            itemRarity: (string) ($dungeon['completionItemRarity'] ?? 'RARE'),
        );
    }
}
