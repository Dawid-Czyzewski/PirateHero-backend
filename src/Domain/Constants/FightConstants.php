<?php

declare(strict_types=1);

namespace App\Domain\Constants;


final class FightConstants
{
    public const DUEL_COST_POINTS = 2;

    public const PVP_WIN_FAME = 30;

    public const PVP_LOSE_FAME = 30;

    public const SHIPS_WIN_FAME = 50;

    public const SHIPS_LOSE_FAME = 50;

    public const DUEL_HP_MULTIPLIER = 2;

    public const CRIT_DAMAGE_MULTIPLIER = 1.5;

    public const DAMAGE_VARIANCE_MIN = 90;

    public const DAMAGE_VARIANCE_MAX = 110;

    public const DAMAGE_VARIANCE_DIVISOR = 100;

    public const EMPTY_STAT_DODGE_OR_CRIT_PERCENT = 50;

    public const MITIGATION_DENOMINATOR_BASE = 40;

    public const MAX_MITIGATION_PERCENT = 30;

    public const SIMILAR_OPPONENTS_LIMIT = 5;
}
