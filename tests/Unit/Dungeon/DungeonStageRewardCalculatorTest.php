<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dungeon;

use App\Dungeon\DungeonStageRewardCalculator;
use App\Enum\DungeonId;
use PHPUnit\Framework\TestCase;

final class DungeonStageRewardCalculatorTest extends TestCase
{
    private DungeonStageRewardCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new DungeonStageRewardCalculator();
    }

    public function testKryptaStageOneReturnsGoldAndScaledExp(): void
    {
        $reward = $this->calculator->forStage(DungeonId::Krypta, 1);

        self::assertSame(40, $reward->gold);
        self::assertSame(8, $reward->exp);
        self::assertFalse($reward->isEmpty());
    }

    public function testKryptaStageFiveScalesExpByStage(): void
    {
        $reward = $this->calculator->forStage(DungeonId::Krypta, 5);

        self::assertSame(40, $reward->gold);
        self::assertSame(40, $reward->exp);
    }

    public function testKrakenStageThreeReturnsGoldAndScaledExp(): void
    {
        $reward = $this->calculator->forStage(DungeonId::Kraken, 3);

        self::assertSame(70, $reward->gold);
        self::assertSame(30, $reward->exp);
        self::assertFalse($reward->isEmpty());
    }

    public function testPalacStageTenReturnsHighestTierRewards(): void
    {
        $reward = $this->calculator->forStage(DungeonId::Palac, 10);

        self::assertSame(166, $reward->gold);
        self::assertSame(160, $reward->exp);
        self::assertFalse($reward->isEmpty());
    }

    public function testInvalidStageReturnsEmptyReward(): void
    {
        self::assertTrue($this->calculator->forStage(DungeonId::Krypta, 0)->isEmpty());
        self::assertTrue($this->calculator->forStage(DungeonId::Krypta, 11)->isEmpty());
    }
}
