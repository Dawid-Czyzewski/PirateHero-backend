<?php

declare(strict_types=1);

namespace App\Tests\Unit\Progression;

use App\Progression\PlayerLevelTable;
use PHPUnit\Framework\TestCase;

final class PlayerLevelTableTest extends TestCase
{
    public function testHistoricalCurveForFirstTenLevels(): void
    {
        self::assertSame(220, PlayerLevelTable::expToNextLevel(1));
        self::assertSame(500, PlayerLevelTable::expToNextLevel(2));
        self::assertSame(900, PlayerLevelTable::expToNextLevel(3));
        self::assertSame(3700, PlayerLevelTable::expToNextLevel(10));
    }

    public function testGeneratesOneHundredRows(): void
    {
        $rows = PlayerLevelTable::rows();
        self::assertCount(100, $rows);
        self::assertSame('1', $rows[0]['name']);
        self::assertSame('100', $rows[99]['name']);
        self::assertSame(39700, $rows[99]['expToNextLevel']);
    }
}
