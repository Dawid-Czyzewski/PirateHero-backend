<?php

declare(strict_types=1);

namespace App\Domain\Constants;

final class DailyChallengeConstants
{
    public const SLOT_COUNT = 3;

    public static function targetForType(string $type, int $level): int
    {
        $level = max(1, $level);

        return match ($type) {
            'missions' => 2 + (int) floor($level / 25),
            'arena_wins' => $level < 20 ? 1 : 2,
            'gold_spent' => max(100, $level * 20),
            default => 1,
        };
    }

    /**
     * @return array{gold: int, exp: int}
     */
    public static function slotReward(int $level): array
    {
        $level = max(1, $level);

        return [
            'gold' => 50 + $level * 5,
            'exp' => 20 + $level * 2,
        ];
    }

    /**
     * @return array{gold: int, diamonds: int}
     */
    public static function bonusReward(int $level): array
    {
        $level = max(1, $level);

        return [
            'gold' => 150 + $level * 10,
            'diamonds' => max(1, (int) floor($level / 20) + 1),
        ];
    }
}
