<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Combat;

use App\Service\Combat\CombatMathService;
use PHPUnit\Framework\TestCase;

final class CombatMathServiceTest extends TestCase
{
    public function testCriticalHitChanceReturnsFiftyWhenBothZero(): void
    {
        $sut = new CombatMathService();
        self::assertSame(50, $sut->criticalHitChancePercentForAttacker(0, 0));
    }

    public function testCriticalHitChanceUsesAttackerShare(): void
    {
        $sut = new CombatMathService();
        self::assertSame(75, $sut->criticalHitChancePercentForAttacker(30, 10));
    }

    public function testDodgeChanceReturnsFiftyWhenBothZero(): void
    {
        $sut = new CombatMathService();
        self::assertSame(50, $sut->dodgeChancePercentForDefender(0, 0));
    }

    public function testDodgeChanceUsesDefenderShare(): void
    {
        $sut = new CombatMathService();
        self::assertSame(25, $sut->dodgeChancePercentForDefender(30, 10));
    }

    public function testCalculateDamageStaysWithinExpectedBounds(): void
    {
        $sut = new CombatMathService();
        $strength = 100;
        for ($i = 0; $i < 40; ++$i) {
            $damage = $sut->calculateDamage($strength);
            self::assertGreaterThanOrEqual(90, $damage);
            self::assertLessThanOrEqual(110, $damage);
        }
    }

    public function testMitigationIsCappedAndNeverZeroesPositiveHit(): void
    {
        $sut = new CombatMathService();
        $after = $sut->mitigateDamageByIntelligence(100, 500, 10);
        self::assertLessThanOrEqual(100, $after);
        self::assertGreaterThanOrEqual(70, $after);
        self::assertGreaterThanOrEqual(1, $after);
    }
}
