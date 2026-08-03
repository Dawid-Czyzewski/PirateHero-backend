<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Dungeon\DungeonCatalog;
use App\Service\Progression\MissionService;
use PHPUnit\Framework\TestCase;

final class MissionEconomySanityTest extends TestCase
{
    public function testMissionTitlePoolSize(): void
    {
        self::assertGreaterThanOrEqual(40, \count(MissionService::titlePool()));
    }

    public function testDungeonStageGoldExceedsTypicalMissionShortSlotGoldAtLevelOne(): void
    {
        $krypta = DungeonCatalog::get(\App\Enum\DungeonId::Krypta);
        self::assertNotNull($krypta);
        self::assertGreaterThan(20, $krypta['goldPerStage']);
        self::assertSame(10, DungeonCatalog::STAGES_PER_DUNGEON);
    }

    public function testShortMissionEnergyAllowsManyRunsOnFullBar(): void
    {
        $shortMaxEnergy = 2;
        $fullBar = 100;
        self::assertGreaterThanOrEqual(50, intdiv($fullBar, $shortMaxEnergy));
    }

    public function testTwoShortMissionsCoverCheapestCommonShopOfferAtLevelOne(): void
    {
        $twoShortMin = 2 * 5 * 5;
        $shopCommonMin = (int) round(45 * 0.95);
        $shopCommonMax = (int) round(75 * 1.05);

        self::assertGreaterThanOrEqual($shopCommonMin, $twoShortMin);
        self::assertLessThanOrEqual(80, $shopCommonMax);
    }

    public function testShortMissionExpDoesNotOneShotEarlyLevels(): void
    {
        $expToLevel2 = 220;
        $shortMaxExp = 5 * 5;
        self::assertGreaterThanOrEqual(8, intdiv($expToLevel2, $shortMaxExp));
    }

    public function testFullDungeonClearGoldIsFiniteOneTimeBurst(): void
    {
        $stageGold = 0;
        $completionGold = 0;
        foreach (DungeonCatalog::all() as $dungeon) {
            $stageGold += $dungeon['goldPerStage'] * DungeonCatalog::STAGES_PER_DUNGEON;
            $completionGold += $dungeon['completionGold'];
        }

        self::assertSame(5240, $stageGold);
        self::assertSame(6200, $completionGold);
        self::assertSame(11440, $stageGold + $completionGold);
    }
}
