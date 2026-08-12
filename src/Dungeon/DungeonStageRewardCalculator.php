<?php

declare(strict_types=1);

namespace App\Dungeon;

use App\Domain\Constants\DungeonConstants;
use App\Enum\DungeonDifficulty;
use App\Enum\DungeonId;


final class DungeonStageRewardCalculator
{
    public function forStage(
        DungeonId $dungeonId,
        int $stage,
        DungeonDifficulty $difficulty = DungeonDifficulty::Normal,
    ): DungeonStageReward {
        $dungeon = DungeonCatalog::get($dungeonId);
        if ($dungeon === null || !($dungeon['rewardsEnabled'] ?? false)) {
            return new DungeonStageReward(0, 0);
        }

        if ($stage < 1 || $stage > DungeonCatalog::STAGES_PER_DUNGEON) {
            return new DungeonStageReward(0, 0);
        }

        $mult = $difficulty === DungeonDifficulty::Hard
            ? DungeonConstants::HARD_STAGE_REWARD_MULTIPLIER
            : 1.0;

        $goldPerStage = (int) ($dungeon['goldPerStage'] ?? 0);
        $expPerStage = (int) ($dungeon['expPerStage'] ?? 0);

        return new DungeonStageReward(
            gold: max(0, (int) round($goldPerStage * $mult)),
            exp: max(0, (int) round($expPerStage * $stage * $mult)),
        );
    }
}
