<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Entity\Ship;
use App\Entity\User;
use App\Entity\Work;
use App\Service\Economy\ShipPercentRewardMath;
use App\Service\ShopBoosters\ShopBoosterSessionService;

final class WorkRewardCalculator
{
    public function __construct(
        private readonly ShopBoosterSessionService $shopBoosterSessionService,
    ) {
    }

    /**
     * @return array{
     *   totalGold: int,
     *   totalGoldAfterShip: int,
     *   perHourBaseGold: int,
     *   bonusPercent: int,
     *   shopBoosterPercent: int
     * }
     */
    public function calculate(User $user, Work $work, ?Ship $ship = null): array
    {
        $this->shopBoosterSessionService->pruneExpiredSessions($user);

        $levelValue = UserLevelResolver::of($user);
        $baseGold = $work->getBaseGold() * $levelValue * $work->getHoursCount();
        $bonusPercent = $ship?->getWorkUpgrade() ?? 0;
        $totalAfterShip = ShipPercentRewardMath::apply($baseGold, $bonusPercent);

        $shopFrac = $this->shopBoosterSessionService->getWorkShopBoosterFraction($user);
        $goldFromShop = (int) floor($totalAfterShip * $shopFrac);
        $multiplier = 1 + ($bonusPercent / 100);

        return [
            'totalGold' => $totalAfterShip + $goldFromShop,
            'totalGoldAfterShip' => $totalAfterShip,
            'perHourBaseGold' => (int) round($work->getBaseGold() * $multiplier),
            'bonusPercent' => $bonusPercent,
            'shopBoosterPercent' => (int) round($shopFrac * 100),
        ];
    }
}
