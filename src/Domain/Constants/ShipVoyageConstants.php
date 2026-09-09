<?php

declare(strict_types=1);

namespace App\Domain\Constants;

final class ShipVoyageConstants
{
    /** @var list<int> */
    public const OFFER_DURATIONS_SECONDS = [7200, 14400, 28800];

    public const TREASURY_SHARE = 0.2;

    public static function isValidDuration(int $durationSeconds): bool
    {
        return \in_array($durationSeconds, self::OFFER_DURATIONS_SECONDS, true);
    }

    public static function hoursFromDuration(int $durationSeconds): float
    {
        return max(1.0, $durationSeconds / 3600);
    }

    public static function treasuryCost(int $durationSeconds, int $enrolledCrewCount): int
    {
        $hours = self::hoursFromDuration($durationSeconds);

        return (int) max(1, round(80 * $hours + 15 * max(1, $enrolledCrewCount)));
    }

    public static function baseGold(int $durationSeconds, int $enrolledCrewCount): int
    {
        $hours = self::hoursFromDuration($durationSeconds);
        $crewFactor = 0.7 + 0.05 * max(1, $enrolledCrewCount);

        return (int) max(1, round(40 * $hours * $crewFactor * 6));
    }

    public static function baseExp(int $durationSeconds, int $enrolledCrewCount): int
    {
        $hours = self::hoursFromDuration($durationSeconds);
        $crewFactor = 0.7 + 0.05 * max(1, $enrolledCrewCount);

        return (int) max(1, round(25 * $hours * $crewFactor * 6));
    }

    public static function shipFame(int $durationSeconds, int $hullUpgrade): int
    {
        $hours = self::hoursFromDuration($durationSeconds);

        return (int) max(1, round($hours * (2 + $hullUpgrade / 5)));
    }
}
