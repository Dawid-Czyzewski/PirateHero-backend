<?php

declare(strict_types=1);

namespace App\Service\Economy;

final class ShipPercentRewardMath
{
    public static function apply(int $base, int $bonusPercent): int
    {
        $multiplier = 1 + ($bonusPercent / 100);
        $afterShip = (int) round($base * $multiplier);
        if ($bonusPercent > 0 && $base > 0 && $afterShip <= $base) {
            return $base + 1;
        }

        return $afterShip;
    }
}
