<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Combat;

use App\Enum\FightMoveResult;
use App\Service\Combat\CombatMathService;
use App\Service\Combat\TurnBasedDuelResolver;
use App\Tests\TestDoubles\FixedSequenceRandomizer;
use PHPUnit\Framework\TestCase;

final class TurnBasedDuelResolverTest extends TestCase
{
    public function testDodgeProducesZeroDamageWhenDefenderHasAgility(): void
    {
        $combatMath = new CombatMathService(new FixedSequenceRandomizer([100]));
        $sut = new TurnBasedDuelResolver($combatMath, new FixedSequenceRandomizer([1]));

        $attacker = ['health' => 50, 'strength' => 40, 'agility' => 10, 'luck' => 10, 'intelligence' => 0];
        $defender = ['health' => 50, 'strength' => 40, 'agility' => 10, 'luck' => 10, 'intelligence' => 0];

        $moves = $sut->resolve(100, 100, $attacker, $defender);
        self::assertSame(FightMoveResult::DODGE, $moves[0]['result']);
        self::assertSame(0, $moves[0]['damage']);
    }

    public function testHigherAgilityStrikesFirst(): void
    {
        $combatMath = new CombatMathService(new FixedSequenceRandomizer([100]));
        $sut = new TurnBasedDuelResolver($combatMath, new FixedSequenceRandomizer([100, 100]));

        $attacker = ['health' => 50, 'strength' => 200, 'agility' => 20, 'luck' => 100, 'intelligence' => 0];
        $defender = ['health' => 50, 'strength' => 5, 'agility' => 5, 'luck' => 0, 'intelligence' => 0];

        $moves = $sut->resolve(100, 40, $attacker, $defender);
        self::assertTrue($moves[0]['isAttackerTurn']);
        self::assertSame(0, $moves[0]['defenderHealthAfter']);
    }

    public function testIntelligenceMitigationReducesDamage(): void
    {
        $combatMath = new CombatMathService(new FixedSequenceRandomizer([100]));
        $sut = new TurnBasedDuelResolver($combatMath, new FixedSequenceRandomizer([100, 100]));

        $attacker = ['health' => 50, 'strength' => 100, 'agility' => 20, 'luck' => 0, 'intelligence' => 0];
        $defender = ['health' => 50, 'strength' => 5, 'agility' => 0, 'luck' => 0, 'intelligence' => 80];

        $moves = $sut->resolve(100, 500, $attacker, $defender);
        self::assertNotSame(FightMoveResult::DODGE, $moves[0]['result']);
        self::assertGreaterThan(0, $moves[0]['damage']);
        self::assertLessThan(100, $moves[0]['damage']);
    }
}
