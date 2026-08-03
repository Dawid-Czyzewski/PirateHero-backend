<?php

declare(strict_types=1);

namespace App\Domain\Constants;

final class EconomyConstants
{
    public const SELL_BACK_RATIO = 0.5;

    public const REFILL_COST_PER_LEVEL = 100;

    public const BASE_ENERGY_CAPACITY = 100;

    public const BASE_TRAINING_CAPACITY = 10;

    public const BASE_FIGHT_CAPACITY = 10;

    public const STORAGE_SLOT_COUNT = 12;

    public const STARTER_TRAINING_POINTS = 10;

    public const STARTER_DUEL_POINTS = 10;

    public const STARTER_FAME_POINTS = 0;

    public const STARTER_BASE_STAT = 30;

    public const STARTER_SKILL_POINT_PRICE = 5;

    public const STARTER_LEVELS_REACHED = 1;

    /** @var array<string, list<int>> keys match {@see \App\Enum\BoosterType} values */
    public const BOOSTER_GOLD_PRICES_BY_TYPE = [
        'ENERGY' => [50, 100],
        'TRAINING_POINTS' => [75, 150],
        'DUEL_POINTS' => [75, 150],
        'SKILLS' => [100, 200],
    ];

    public const BOOSTER_PRICE_LEVEL_STEP = 0.1;

    public const BOOSTER_PRICE_JITTER_PERCENT = 20;

    public const BOOSTER_DIAMOND_FALLBACK_PRICE = 5;

    public const BOOSTER_GOLD_MAX_TIER = 2;
}
