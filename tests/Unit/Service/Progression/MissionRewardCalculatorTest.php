<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Entity\Level;
use App\Entity\Mission;
use App\Entity\Ship;
use App\Entity\User;
use App\Service\Progression\MissionRewardCalculator;
use App\Service\ShopBoosters\ShopBoosterSessionService;
use PHPUnit\Framework\TestCase;

final class MissionRewardCalculatorTest extends TestCase
{
    public function testAppliesShipPercentThenShopBooster(): void
    {
        $shop = $this->createMock(ShopBoosterSessionService::class);
        $shop->expects(self::once())->method('pruneExpiredSessions');
        $shop->method('getMissionShopBoosterFraction')->willReturn(0.1);

        $ship = $this->createMock(Ship::class);
        $ship->method('getMissionsUpgrade')->willReturn(10);

        $user = $this->makeUser();
        $mission = (new Mission())
            ->setTitle('t')
            ->setGoldReward(100)
            ->setExpReward(200)
            ->setDurationInSeconds(300)
            ->setEnergyCost(10)
            ->setUser($user);

        $result = (new MissionRewardCalculator($shop))->calculate($user, $mission, $ship);

        self::assertSame(121, $result['gold']);
        self::assertSame(242, $result['exp']);
        self::assertSame(10, $result['bonusPercent']);
        self::assertSame(10, $result['shopBoosterPercent']);
    }

    public function testMinimumBumpWhenShipRoundingWouldLeaveFlat(): void
    {
        $shop = $this->createMock(ShopBoosterSessionService::class);
        $shop->method('pruneExpiredSessions');
        $shop->method('getMissionShopBoosterFraction')->willReturn(0.0);

        $ship = $this->createMock(Ship::class);
        $ship->method('getMissionsUpgrade')->willReturn(1);

        $user = $this->makeUser();
        $mission = (new Mission())
            ->setTitle('t')
            ->setGoldReward(15)
            ->setExpReward(91)
            ->setDurationInSeconds(300)
            ->setEnergyCost(5)
            ->setUser($user);

        $result = (new MissionRewardCalculator($shop))->calculate($user, $mission, $ship);

        self::assertSame(16, $result['gold']);
        self::assertSame(92, $result['exp']);
    }

    private function makeUser(): User
    {
        $level = (new Level())->setName('1')->setExpToNextLevel(220);

        return (new User())
            ->setEmail(sprintf('calc_%s@test.local', bin2hex(random_bytes(4))))
            ->setUsername(sprintf('c_%s', bin2hex(random_bytes(3))))
            ->setPassword('hash')
            ->setLevel($level)
            ->setGold(100)
            ->setdiamonds(10)
            ->setEnergyPoints(100)
            ->setTrainingPoints(10)
            ->setDuelPoints(10)
            ->setFamePoints(10);
    }
}
