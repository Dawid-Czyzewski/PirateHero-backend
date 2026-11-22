<?php

declare(strict_types=1);

namespace App\Tests\Unit\Combat\Domain;

use App\Combat\Domain\Model\Fighter;
use App\Combat\Domain\Model\FighterSide;
use App\Combat\Domain\Model\FightLog;
use App\Combat\Domain\Model\FightResult;
use App\Combat\Domain\Model\Reward;
use App\Combat\Domain\Service\RewardPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RewardPolicyTest extends TestCase
{
    /**
     * @return iterable<string, array{int, int, int}>
     */
    public static function victories(): iterable
    {
        yield 'level 1' => [1, 25, 15];
        yield 'level 3' => [3, 35, 25];
        yield 'level 8' => [8, 60, 50];
    }

    #[DataProvider('victories')]
    public function testVictoryPaysByNpcLevel(int $level, int $experience, int $onions): void
    {
        self::assertEquals(new Reward($experience, $onions), self::reward(FighterSide::Player, $level));
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function defeats(): iterable
    {
        yield 'level 1' => [1, 6];
        yield 'level 3' => [3, 8];
        yield 'level 8' => [8, 15];
    }

    #[DataProvider('defeats')]
    public function testDefeatPaysAQuarterOfTheExperienceAndNoOnions(int $level, int $experience): void
    {
        self::assertEquals(new Reward($experience, 0), self::reward(FighterSide::Npc, $level));
    }

    public function testRewardIsReadyForTheFrontend(): void
    {
        self::assertSame(['experience' => 25, 'onions' => 15], self::reward(FighterSide::Player, 1)->toArray());
    }

    private static function reward(FighterSide $winner, int $npcLevel): Reward
    {
        return new RewardPolicy()->for(
            new FightResult($winner, true, new FightLog([])),
            new Fighter('NPC', $npcLevel, 1, 1, 1, 1),
        );
    }
}
