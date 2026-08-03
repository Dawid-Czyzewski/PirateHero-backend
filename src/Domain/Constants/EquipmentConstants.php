<?php

declare(strict_types=1);

namespace App\Domain\Constants;


final class EquipmentConstants
{
    public const PRICE_PER_LEVEL = 20;

    public const STRONG_POINTS_MIN = 1;

    public const STRONG_POINTS_MAX = 5;

    public const AGILITY_POINTS_MIN = 1;

    public const AGILITY_POINTS_MAX = 5;

    public const INTELLIGENCE_POINTS_MIN = 0;

    public const INTELLIGENCE_POINTS_MAX = 3;

    public const CRITICAL_CHANCE_POINTS_MIN = 0;

    public const CRITICAL_CHANCE_POINTS_MAX = 3;

    public const HEALTH_POINTS_MIN = 5;

    public const HEALTH_POINTS_MAX = 10;

    /** @var array<string, float> */
    public const RARITY_MODIFIERS = [
        'COMMON' => 1.0,
        'UNCOMMON' => 1.2,
        'RARE' => 1.5,
        'EPIC' => 2.0,
        'LEGENDARY' => 3.0,
    ];

    /**
     * Hero Zero–style rarity roll (shop / loot cosmetics), plus a thin legendary band.
     * Common-tier ≈69%, Rare ≈25%, Epic ≈5%, Legendary ≈1%.
     *
     * @var array<string, int>
     */
    public const RARITY_DROP_WEIGHTS = [
        'COMMON' => 54,
        'UNCOMMON' => 15,
        'RARE' => 25,
        'EPIC' => 5,
        'LEGENDARY' => 1,
    ];
}
