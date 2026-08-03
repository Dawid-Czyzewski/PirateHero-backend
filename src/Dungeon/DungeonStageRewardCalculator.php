<?php

declare(strict_types=1);

namespace App\Dungeon;

use App\Enum\DungeonId;


final class DungeonStageRewardCalculator
{
    public function forStage(DungeonId $dungeonId, int $stage): DungeonStageReward
    {
        $dungeon = DungeonCatalog::get($dungeonId);
        if ($dungeon === null || !($dungeon['rewardsEnabled'] ?? false)) {
            return new DungeonStageReward(0, 0);
        }

        if ($stage < 1 || $stage > DungeonCatalog::STAGES_PER_DUNGEON) {
            return new DungeonStageReward(0, 0);
        }

        $goldPerStage = (int) ($dungeon['goldPerStage'] ?? 0);
        $expPerStage = (int) ($dungeon['expPerStage'] ?? 0);

        return new DungeonStageReward(
            gold: max(0, $goldPerStage),
            exp: max(0, $expPerStage * $stage),
        );
    }
}
