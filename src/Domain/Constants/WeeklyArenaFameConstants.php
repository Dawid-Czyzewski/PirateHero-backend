<?php

declare(strict_types=1);

namespace App\Domain\Constants;

use App\Service\Progression\TitleCodes;

final class WeeklyArenaFameConstants
{
    /** @var list<int> */
    public const TIER_THRESHOLDS = [90, 300, 600];

    /**
     * @return array{gold: int, diamonds: int}
     */
    public static function claimReward(int $tier, int $level): array
    {
        $level = max(1, $level);
        $tier = max(1, min(3, $tier));

        return match ($tier) {
            1 => [
                'gold' => 200 + $level * 15,
                'diamonds' => max(1, (int) floor($level / 20)),
            ],
            2 => [
                'gold' => 450 + $level * 30,
                'diamonds' => max(2, 1 + (int) floor($level / 15)),
            ],
            default => [
                'gold' => 800 + $level * 50,
                'diamonds' => max(3, 2 + (int) floor($level / 12)),
            ],
        };
    }

    public static function titleCodeForTier(int $tier): string
    {
        return match ($tier) {
            1 => TitleCodes::WEEKLY_ARENA_FIGHTER,
            2 => TitleCodes::WEEKLY_ARENA_CHAMPION,
            default => TitleCodes::WEEKLY_ARENA_LEGEND,
        };
    }

    public static function thresholdForTier(int $tier): int
    {
        $tier = max(1, min(3, $tier));

        return self::TIER_THRESHOLDS[$tier - 1];
    }
}
