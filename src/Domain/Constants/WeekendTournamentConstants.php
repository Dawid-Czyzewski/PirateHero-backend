<?php

declare(strict_types=1);

namespace App\Domain\Constants;

use App\Service\Progression\TitleCodes;

final class WeekendTournamentConstants
{
    public const MIN_POINTS_TO_CLAIM = 3;

    public const LEADERBOARD_LIMIT = 10;

    /**
     * @return array{gold: int, diamonds: int, band: string}
     */
    public static function claimReward(?int $rank, int $level): array
    {
        $level = max(1, $level);

        if ($rank === 1) {
            return [
                'gold' => 2000 + $level * 80,
                'diamonds' => max(5, 3 + (int) floor($level / 10)),
                'band' => 'rank1',
            ];
        }
        if ($rank !== null && $rank >= 2 && $rank <= 3) {
            return [
                'gold' => 1200 + $level * 50,
                'diamonds' => max(3, 2 + (int) floor($level / 12)),
                'band' => 'rank2_3',
            ];
        }
        if ($rank !== null && $rank >= 4 && $rank <= 10) {
            return [
                'gold' => 700 + $level * 30,
                'diamonds' => max(2, 1 + (int) floor($level / 15)),
                'band' => 'rank4_10',
            ];
        }

        return [
            'gold' => 300 + $level * 15,
            'diamonds' => max(1, (int) floor($level / 20)),
            'band' => 'participation',
        ];
    }

    public static function titleCodeForClaim(?int $rank): string
    {
        if ($rank === 1) {
            return TitleCodes::WEEKEND_ARENA_CHAMPION;
        }

        return TitleCodes::WEEKEND_GLADIATOR;
    }
}
