<?php

declare(strict_types=1);

namespace App\Domain\Constants;

final class CompanionConstants
{
    public const CODE_PARROT = 'parrot';

    public const UNLOCK_LEVEL = 5;

    public const MAX_LEVEL = 10;

    public const FEED_GOLD_COST = 50;

    public const FEED_COOLDOWN_HOURS = 4;

    public const ABILITY_COOLDOWN_HOURS = 24;

    public const ABILITY_SHORTEN_MISSION_MINUTES = 10;

    public static function workGoldBonusPercent(int $level): int
    {
        $level = max(0, min(self::MAX_LEVEL, $level));

        return $level;
    }
}
