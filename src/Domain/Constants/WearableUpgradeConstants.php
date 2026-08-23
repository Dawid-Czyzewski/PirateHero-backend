<?php

declare(strict_types=1);

namespace App\Domain\Constants;

use App\Enum\WearableItemRarity;

final class WearableUpgradeConstants
{
    public const GOLD_BASE = 100;

    /** @var array<string, int> */
    public const MAX_LEVEL_BY_RARITY = [
        'COMMON' => 3,
        'UNCOMMON' => 4,
        'RARE' => 5,
        'EPIC' => 7,
        'LEGENDARY' => 10,
    ];

    public static function maxLevelFor(?WearableItemRarity $rarity): int
    {
        if ($rarity === null) {
            return self::MAX_LEVEL_BY_RARITY['COMMON'];
        }

        return self::MAX_LEVEL_BY_RARITY[$rarity->value] ?? self::MAX_LEVEL_BY_RARITY['COMMON'];
    }

    public static function goldCost(int $currentUpgradeLevel, ?WearableItemRarity $rarity): int
    {
        $modifier = EquipmentConstants::RARITY_MODIFIERS[$rarity?->value ?? 'COMMON'] ?? 1.0;

        return max(1, (int) round(self::GOLD_BASE * ($currentUpgradeLevel + 1) * $modifier));
    }
}
