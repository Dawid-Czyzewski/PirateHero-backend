<?php

declare(strict_types=1);

namespace App\Domain\Constants;


final class DungeonConstants
{
    public const STAGE_HP_SCALE = 0.35;

    public const STAGE_DAMAGE_SCALE = 0.2;

    public const HP_TO_ENDURANCE_DIVISOR = 3;

    public const MIN_STRENGTH = 5;

    public const AGILITY_FROM_STRENGTH_RATIO = 0.85;

    public const MIN_AGILITY = 5;

    public const BASE_INTELLIGENCE = 10;

    public const BASE_LUCK = 6;

    public const MAX_ROUNDS = 20;

    public const HP_POOL_MULTIPLIER = 3;
}
