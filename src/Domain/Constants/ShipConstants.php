<?php

declare(strict_types=1);

namespace App\Domain\Constants;


final class ShipConstants
{
    public const BASE_CREW_SLOTS = 10;

    public const CLUB_CREATION_COST = 500;

    public const MAX_HULL_UPGRADE_LEVEL = 15;

    public const MAX_UPGRADE_LEVEL = 50;

    public const LEGACY_BASE_GOLD = 150;

    public const LEGACY_GOLD_STEP = 30;

    public const LEGACY_BASE_DIAMONDS = 40;

    public const LEGACY_DIAMONDS_STEP = 20;

    /** @var array<string, int> */
    public const MAX_UPGRADE_BY_TYPE = [
        'skills' => 50,
        'work' => 50,
        'missions' => 50,
        'hull' => 15,
    ];
}
