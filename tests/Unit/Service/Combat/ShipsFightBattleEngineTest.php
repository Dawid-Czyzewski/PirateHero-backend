<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Combat;

use App\Entity\ShipsFight;
use App\Entity\User;
use App\Enum\FightMoveResult;
use App\Service\Combat\CombatMathService;
use App\Service\Combat\ShipsFightBattleEngine;
use App\Service\ShopBoosters\ShopBoosterSessionService;
use App\Tests\TestDoubles\FixedSequenceRandomizer;
use PHPUnit\Framework\TestCase;

final class ShipsFightBattleEngineTest extends TestCase
{
    public function testDodgePathProducesZeroDamageThenAttackerLosesOnFollowUpHit(): void
    {
        $attackerUser = $this->createMock(User::class);
        $defenderUser = $this->createMock(User::class);

        $attackerStats = ['health' => 1, 'strength' => 5, 'agility' => 0, 'luck' => 0, 'intelligence' => 0];
        $defenderStats = ['health' => 50, 'strength' => 200, 'agility' => 100, 'luck' => 0, 'intelligence' => 0];

        $shopBoosterSessionService = $this->makeShopBoosterSessionServiceMock(
            $attackerUser,
            $attackerStats,
            $defenderUser,
            $defenderStats,
        );

        $combatMath = new CombatMathService(new FixedSequenceRandomizer([100]));
        $engine = new ShipsFightBattleEngine(
            $combatMath,
            $shopBoosterSessionService,
            new FixedSequenceRandomizer([1, 100, 100]),
        );

        $fight = new ShipsFight();
        $result = $engine->simulate($fight, [$attackerUser], [$defenderUser]);

        self::assertCount(2, $result['moves']);
        self::assertSame(FightMoveResult::DODGE, $result['moves'][0]->getResult());
        self::assertSame(0, $result['moves'][0]->getDamage());
        self::assertSame(100, $result['moves'][0]->getTargetHealthAfter());

        self::assertSame(FightMoveResult::HIT, $result['moves'][1]->getResult());
        self::assertSame(0, $result['moves'][1]->getTargetHealthAfter());

        self::assertTrue($result['attackerFightMembers'][0]['member']->isDefeated());
        self::assertFalse($result['attackerWon']);
    }

    public function testHitPathDealsDamageAndDefeatsDefenderInOneMove(): void
    {
        $attackerUser = $this->createMock(User::class);
        $defenderUser = $this->createMock(User::class);

        $attackerStats = ['health' => 50, 'strength' => 200, 'agility' => 20, 'luck' => 0, 'intelligence' => 0];
        $defenderStats = ['health' => 20, 'strength' => 5, 'agility' => 5, 'luck' => 100, 'intelligence' => 0];

        $shopBoosterSessionService = $this->makeShopBoosterSessionServiceMock(
            $attackerUser,
            $attackerStats,
            $defenderUser,
            $defenderStats,
        );

        $combatMath = new CombatMathService(new FixedSequenceRandomizer([100]));
        $engine = new ShipsFightBattleEngine(
            $combatMath,
            $shopBoosterSessionService,
            new FixedSequenceRandomizer([100, 100]),
        );

        $fight = new ShipsFight();
        $result = $engine->simulate($fight, [$attackerUser], [$defenderUser]);

        self::assertCount(1, $result['moves']);
        self::assertSame(FightMoveResult::HIT, $result['moves'][0]->getResult());
        self::assertGreaterThan(0, $result['moves'][0]->getDamage());
        self::assertSame(0, $result['moves'][0]->getTargetHealthAfter());

        self::assertTrue($result['defenderFightMembers'][0]['member']->isDefeated());
        self::assertTrue($result['attackerWon']);
    }

    /**
     * @param array<string, int> $attackerStats
     * @param array<string, int> $defenderStats
     */
    private function makeShopBoosterSessionServiceMock(
        User $attackerUser,
        array $attackerStats,
        User $defenderUser,
        array $defenderStats,
    ): ShopBoosterSessionService {
        $mock = $this->createMock(ShopBoosterSessionService::class);
        $mock->method('getCombatStatistics')->willReturnCallback(
            static function (User $user) use ($attackerUser, $attackerStats, $defenderStats): array {
                return $user === $attackerUser ? $attackerStats : $defenderStats;
            }
        );

        return $mock;
    }
}
