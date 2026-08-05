<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Constants;

use App\Domain\Constants\MissionConstants;
use PHPUnit\Framework\TestCase;

final class MissionConstantsSkipCostTest extends TestCase
{
    public function testDiamondCostToSkipBands(): void
    {
        self::assertSame(0, MissionConstants::diamondCostToSkip(0));
        self::assertSame(1, MissionConstants::diamondCostToSkip(1));
        self::assertSame(1, MissionConstants::diamondCostToSkip(60));
        self::assertSame(2, MissionConstants::diamondCostToSkip(61));
        self::assertSame(5, MissionConstants::diamondCostToSkip(300));
        self::assertSame(5, MissionConstants::diamondCostToSkip(3600));
    }

    public function testRemainingSeconds(): void
    {
        $start = new \DateTimeImmutable('2026-01-01 12:00:00');
        $now = new \DateTimeImmutable('2026-01-01 12:02:00');
        self::assertSame(180, MissionConstants::remainingSeconds($start, 300, $now));
        self::assertSame(0, MissionConstants::remainingSeconds($start, 60, $now));
    }
}
