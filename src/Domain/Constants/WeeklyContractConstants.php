<?php

declare(strict_types=1);

namespace App\Domain\Constants;

final class WeeklyContractConstants
{
    public static function typeForWeekNumber(int $weekNumber): string
    {
        return match ($weekNumber % 3) {
            0 => 'missions',
            1 => 'arena_wins',
            default => 'gold_spent',
        };
    }

    public static function targetForType(string $type, int $level): int
    {
        $level = max(1, $level);

        return match ($type) {
            'missions' => max(12, (2 + (int) floor($level / 25)) * 6),
            'arena_wins' => max(8, $level < 20 ? 6 : 12),
            'gold_spent' => max(800, $level * 20 * 6),
            default => 1,
        };
    }

    /**
     * @return array{gold: int, diamonds: int}
     */
    public static function claimReward(int $level): array
    {
        $level = max(1, $level);

        return [
            'gold' => 500 + $level * 40,
            'diamonds' => max(2, 1 + (int) floor($level / 15)),
        ];
    }
}
