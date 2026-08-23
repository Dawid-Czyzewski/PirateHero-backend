<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Constants;

use App\Domain\Constants\WearableUpgradeConstants;
use App\Domain\Constants\WeeklyContractConstants;
use App\Enum\WearableItemRarity;
use PHPUnit\Framework\TestCase;

final class WearableAndWeeklyConstantsTest extends TestCase
{
    public function testUpgradeCapsByRarity(): void
    {
        self::assertSame(3, WearableUpgradeConstants::maxLevelFor(WearableItemRarity::COMMON));
        self::assertSame(4, WearableUpgradeConstants::maxLevelFor(WearableItemRarity::UNCOMMON));
        self::assertSame(5, WearableUpgradeConstants::maxLevelFor(WearableItemRarity::RARE));
        self::assertSame(7, WearableUpgradeConstants::maxLevelFor(WearableItemRarity::EPIC));
        self::assertSame(10, WearableUpgradeConstants::maxLevelFor(WearableItemRarity::LEGENDARY));
    }

    public function testUpgradeGoldCostScalesWithLevel(): void
    {
        $l0 = WearableUpgradeConstants::goldCost(0, WearableItemRarity::COMMON);
        $l1 = WearableUpgradeConstants::goldCost(1, WearableItemRarity::COMMON);
        self::assertSame(100, $l0);
        self::assertSame(200, $l1);
        self::assertGreaterThan($l0, WearableUpgradeConstants::goldCost(0, WearableItemRarity::LEGENDARY));
    }

    public function testWeeklyTypeRotation(): void
    {
        self::assertSame('missions', WeeklyContractConstants::typeForWeekNumber(0));
        self::assertSame('arena_wins', WeeklyContractConstants::typeForWeekNumber(1));
        self::assertSame('gold_spent', WeeklyContractConstants::typeForWeekNumber(2));
        self::assertSame('missions', WeeklyContractConstants::typeForWeekNumber(3));
    }

    public function testWeeklyTargets(): void
    {
        self::assertSame(12, WeeklyContractConstants::targetForType('missions', 1));
        self::assertSame(24, WeeklyContractConstants::targetForType('missions', 50));
        self::assertSame(8, WeeklyContractConstants::targetForType('arena_wins', 10));
        self::assertSame(12, WeeklyContractConstants::targetForType('arena_wins', 20));
        self::assertSame(800, WeeklyContractConstants::targetForType('gold_spent', 1));
        self::assertSame(6000, WeeklyContractConstants::targetForType('gold_spent', 50));
    }

    public function testWeeklyClaimReward(): void
    {
        $r = WeeklyContractConstants::claimReward(15);
        self::assertSame(1100, $r['gold']);
        self::assertSame(2, $r['diamonds']);
    }
}
