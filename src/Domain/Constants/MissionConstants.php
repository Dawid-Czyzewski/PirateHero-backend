<?php

declare(strict_types=1);

namespace App\Domain\Constants;


final class MissionConstants
{
    /** @var list<int> */
    public const DURATION_SECONDS = [300, 600, 900, 2100, 3600];

    public const GOLD_PER_MINUTE_MIN = 5;

    public const GOLD_PER_MINUTE_MAX = 8;

    public const EXP_PER_MINUTE_MIN = 3;

    public const EXP_PER_MINUTE_MAX = 5;

    public const LONG_MISSION_MINUTES = 35;

    public const LONG_MISSION_REWARD_BONUS = 1.12;

    public const SECONDS_PER_MINUTE = 60;

    public const SKIP_DIAMOND_COST_MAX = 5;

    public const ENERGY_FALLBACK_LOW_DIVISOR = 600;

    public const ENERGY_FALLBACK_HIGH_DIVISOR = 360;

    /**
     * Hero Zero–style energy bands by mission duration.
     *
     * @var array<int, array{0: int, 1: int}>
     */
    public const ENERGY_BY_DURATION_SECONDS = [
        300 => [1, 2],
        600 => [2, 3],
        900 => [3, 4],
        2100 => [5, 7],
        3600 => [8, 10],
    ];

    public static function remainingSeconds(
        \DateTimeInterface $startTime,
        int $durationSeconds,
        ?\DateTimeInterface $now = null,
    ): int {
        $nowTs = ($now ?? new \DateTimeImmutable())->getTimestamp();
        $elapsed = $nowTs - $startTime->getTimestamp();

        return max(0, $durationSeconds - $elapsed);
    }

    public static function diamondCostToSkip(int $remainingSeconds): int
    {
        if ($remainingSeconds <= 0) {
            return 0;
        }

        $remainingMinutes = (int) ceil($remainingSeconds / self::SECONDS_PER_MINUTE);

        return min(self::SKIP_DIAMOND_COST_MAX, max(1, $remainingMinutes));
    }
}
