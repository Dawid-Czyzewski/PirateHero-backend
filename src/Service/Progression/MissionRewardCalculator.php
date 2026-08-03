<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Entity\Mission;
use App\Entity\Ship;
use App\Entity\User;
use App\Service\Economy\ShipPercentRewardMath;
use App\Service\ShopBoosters\ShopBoosterSessionService;

final class MissionRewardCalculator
{
    public function __construct(
        private readonly ShopBoosterSessionService $shopBoosterSessionService,
    ) {
    }

    /**
     * @return array{
     *   gold: int,
     *   exp: int,
     *   bonusPercent: int,
     *   shopBoosterPercent: int
     * }
     */
    public function calculate(User $user, Mission $mission, ?Ship $ship = null): array
    {
        $this->shopBoosterSessionService->pruneExpiredSessions($user);

        $bonusPercent = $ship?->getMissionsUpgrade() ?? 0;
        $baseGold = $mission->getGoldReward() ?? 0;
        $baseExp = $mission->getExpReward() ?? 0;
        $goldAfterShip = ShipPercentRewardMath::apply($baseGold, $bonusPercent);
        $expAfterShip = ShipPercentRewardMath::apply($baseExp, $bonusPercent);

        $shopFrac = $this->shopBoosterSessionService->getMissionShopBoosterFraction($user);
        $goldFromShop = (int) floor($goldAfterShip * $shopFrac);
        $expFromShop = (int) floor($expAfterShip * $shopFrac);

        return [
            'gold' => $goldAfterShip + $goldFromShop,
            'exp' => $expAfterShip + $expFromShop,
            'bonusPercent' => $bonusPercent,
            'shopBoosterPercent' => (int) round($shopFrac * 100),
        ];
    }
}
