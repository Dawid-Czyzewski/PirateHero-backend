<?php

declare(strict_types=1);

namespace App\Domain\Constants;

use App\Enum\TreasureMapStepType;

final class TreasureMapConstants
{
    /**
     * Sequential steps: type + target.
     *
     * @return list<array{type: string, target: int}>
     */
    public static function steps(): array
    {
        return [
            ['type' => TreasureMapStepType::Missions->value, 'target' => 2],
            ['type' => TreasureMapStepType::ArenaWins->value, 'target' => 1],
            ['type' => TreasureMapStepType::DungeonStageWin->value, 'target' => 1],
            ['type' => TreasureMapStepType::GoldSpent->value, 'target' => 500],
            ['type' => TreasureMapStepType::ShipVoyageComplete->value, 'target' => 1],
        ];
    }

    public static function stepCount(): int
    {
        return \count(self::steps());
    }

    /**
     * @return array{gold: int, diamonds: int}
     */
    public static function chestReward(int $level): array
    {
        $level = max(1, $level);

        return [
            'gold' => 600 + $level * 40,
            'diamonds' => max(2, 1 + (int) floor($level / 15)),
        ];
    }
}
