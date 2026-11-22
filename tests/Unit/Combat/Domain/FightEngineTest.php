<?php

declare(strict_types=1);

namespace App\Tests\Unit\Combat\Domain;

use App\Combat\Domain\Model\CombatBalance;
use App\Combat\Domain\Model\Fighter;
use App\Combat\Domain\Model\FighterSide;
use App\Combat\Domain\Model\Round;
use App\Combat\Domain\Service\FightEngine;
use App\Tests\Double\Combat\SequenceRandomIntProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FightEngineTest extends TestCase
{
    /**
     * @return iterable<string, array{int, int, int}>
     */
    public static function knownRolls(): iterable
    {
        yield 'weakest roll' => [-20, 13, 10];
        yield 'no variance' => [0, 13, 13];
        yield 'strongest roll' => [20, 13, 16];
        yield 'fresh player low' => [-20, 3, 2];
        yield 'fresh player high' => [20, 3, 4];
    }

    #[DataProvider('knownRolls')]
    public function testDamageIsBasePlusMinusTwentyPercent(int $roll, int $baseDamage, int $expected): void
    {
        $attacker = 13 === $baseDamage ? new Fighter('Pała', 4, 5, 3, 3, 2) : self::fresh('Gracz');

        self::assertSame($expected, self::engine($roll)->damage($attacker));
    }

    public function testAsksTheRandomPortForTheConfiguredVariance(): void
    {
        $this->expectException(\LogicException::class);

        self::engine(CombatBalance::DAMAGE_VARIANCE_PERCENT + 1)->damage(self::fresh('Gracz'));
    }

    public function testHigherTechniqueStrikesFirst(): void
    {
        $npc = new Fighter('Cichy', 1, 1, 1, 2, 1);

        $rounds = self::engine(0)->fight(self::fresh('Gracz'), $npc)->log->rounds();

        self::assertSame(FighterSide::Npc, $rounds[0]->attacker);
        self::assertSame(FighterSide::Player, $rounds[1]->attacker);
    }

    public function testPlayerStrikesFirstOnEqualTechnique(): void
    {
        $rounds = self::engine(0)->fight(self::fresh('Gracz'), self::fresh('Kosa'))->log->rounds();

        self::assertSame(FighterSide::Player, $rounds[0]->attacker);
    }

    public function testKnocksOutAndLogsEveryBlow(): void
    {
        $result = self::engine(0)->fight(self::fresh('Gracz'), self::fresh('Kosa'));
        $last = $result->log->rounds()[$result->log->count() - 1];

        self::assertSame(FighterSide::Player, $result->winner);
        self::assertTrue($result->knockout);
        self::assertSame(9, $result->log->count());
        self::assertSame(0, $last->npcHp);
        self::assertSame(3, $last->playerHp);
        self::assertSame(
            [[1, 'player', 3, 15, 12], [2, 'npc', 3, 12, 12]],
            array_map(
                static fn (Round $round): array => [$round->number, $round->attacker->value, $round->damage, $round->playerHp, $round->npcHp],
                \array_slice($result->log->rounds(), 0, 2),
            ),
        );
    }

    public function testNpcCanKnockOutThePlayer(): void
    {
        $result = self::engine(0)->fight(self::fresh('Gracz'), new Fighter('Pała', 1, 5, 1, 1, 1));

        self::assertSame(FighterSide::Npc, $result->winner);
        self::assertTrue($result->knockout);
        self::assertSame(0, $result->log->rounds()[$result->log->count() - 1]->playerHp);
    }

    public function testStopsAfterTwentyRounds(): void
    {
        $tank = new Fighter('Ściana', 1, 1, 50, 1, 1);

        $result = self::engine(0)->fight($tank, $tank);

        self::assertSame(CombatBalance::MAX_ROUNDS, $result->log->count());
        self::assertFalse($result->knockout);
    }

    public function testDecidesOnRemainingHpPercentageNotAbsoluteHp(): void
    {
        $player = new Fighter('Gracz', 1, 3, 5, 1, 1);
        $npc = new Fighter('Kloc', 1, 1, 10, 1, 1);

        $result = self::engine(0)->fight($player, $npc);
        $last = $result->log->rounds()[$result->log->count() - 1];

        self::assertFalse($result->knockout);
        self::assertSame([25, 35], [$last->playerHp, $last->npcHp]);
        self::assertSame(FighterSide::Player, $result->winner);
    }

    public function testPlayerWinsAnEvenDecision(): void
    {
        $tank = new Fighter('Ściana', 1, 1, 50, 1, 1);

        self::assertSame(FighterSide::Player, self::engine(0)->fight($tank, $tank)->winner);
    }

    public function testResultIsReadyForTheFrontend(): void
    {
        $data = self::engine(0)->fight(self::fresh('Gracz'), self::fresh('Kosa'))->toArray();

        self::assertSame('player', $data['winner']);
        self::assertTrue($data['knockout']);
        self::assertSame(['number' => 1, 'attacker' => 'player', 'damage' => 3, 'playerHp' => 15, 'npcHp' => 12], $data['rounds'][0]);
    }

    private static function engine(int $roll): FightEngine
    {
        return new FightEngine(SequenceRandomIntProvider::always($roll));
    }

    private static function fresh(string $name): Fighter
    {
        return new Fighter($name, 1, 1, 1, 1, 1);
    }
}
