<?php

declare(strict_types=1);

namespace App\Domain\Constants;

final class TavernJobConstants
{
    public const DAILY_OFFER_COUNT = 2;

    /**
     * @var list<array{titleKey: string, durationMinutes: int, baseGold: int, rewardDiamonds: int}>
     */
    public const OFFER_DEFINITIONS = [
        [
            'titleKey' => 'tavern.job.errand',
            'durationMinutes' => 15,
            'baseGold' => 25,
            'rewardDiamonds' => 0,
        ],
        [
            'titleKey' => 'tavern.job.cargo',
            'durationMinutes' => 45,
            'baseGold' => 70,
            'rewardDiamonds' => 1,
        ],
    ];

    public static function rewardGold(int $baseGold, int $level): int
    {
        $level = max(1, $level);

        return $baseGold + (int) floor(($level - 1) * 1.5);
    }
}
