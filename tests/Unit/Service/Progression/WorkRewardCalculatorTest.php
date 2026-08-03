<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Entity\Level;
use App\Entity\Ship;
use App\Entity\User;
use App\Entity\Work;
use App\Service\Progression\WorkRewardCalculator;
use App\Service\ShopBoosters\ShopBoosterSessionService;
use PHPUnit\Framework\TestCase;

final class WorkRewardCalculatorTest extends TestCase
{
    public function testAppliesShipPercentThenShopBooster(): void
    {
        $shop = $this->createMock(ShopBoosterSessionService::class);
        $shop->expects(self::once())->method('pruneExpiredSessions');
        $shop->method('getWorkShopBoosterFraction')->willReturn(0.1);

        $ship = $this->createMock(Ship::class);
        $ship->method('getWorkUpgrade')->willReturn(10);

        $user = $this->makeUser('5');
        $work = (new Work())
            ->setTitle('shift')
            ->setBaseGold(20)
            ->setHoursCount(2)
            ->setUser($user);

        $result = (new WorkRewardCalculator($shop))->calculate($user, $work, $ship);

        self::assertSame(242, $result['totalGold']);
        self::assertSame(220, $result['totalGoldAfterShip']);
        self::assertSame(22, $result['perHourBaseGold']);
        self::assertSame(10, $result['bonusPercent']);
        self::assertSame(10, $result['shopBoosterPercent']);
    }

    public function testMinimumBumpWhenShipRoundingWouldLeaveFlat(): void
    {
        $shop = $this->createMock(ShopBoosterSessionService::class);
        $shop->method('pruneExpiredSessions');
        $shop->method('getWorkShopBoosterFraction')->willReturn(0.0);

        $ship = $this->createMock(Ship::class);
        $ship->method('getWorkUpgrade')->willReturn(1);

        $user = $this->makeUser('3');
        $work = (new Work())
            ->setTitle('shift')
            ->setBaseGold(15)
            ->setHoursCount(1)
            ->setUser($user);

        $result = (new WorkRewardCalculator($shop))->calculate($user, $work, $ship);

        self::assertSame(46, $result['totalGold']);
        self::assertSame(46, $result['totalGoldAfterShip']);
    }

    private function makeUser(string $levelName): User
    {
        $level = (new Level())->setName($levelName)->setExpToNextLevel(220);

        return (new User())
            ->setEmail(sprintf('wrc_%s@test.local', bin2hex(random_bytes(4))))
            ->setUsername(sprintf('w_%s', bin2hex(random_bytes(3))))
            ->setPassword('hash')
            ->setLevel($level)
            ->setGold(100)
            ->setDiamonds(10)
            ->setEnergyPoints(100)
            ->setTrainingPoints(10)
            ->setDuelPoints(10)
            ->setFamePoints(10);
    }
}
