<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Constants;

use App\Domain\Constants\ShipVoyageConstants;
use PHPUnit\Framework\TestCase;

final class ShipVoyageConstantsTest extends TestCase
{
    public function testTreasuryCostScalesWithHoursAndCrew(): void
    {
        $solo2h = ShipVoyageConstants::treasuryCost(7200, 1);
        $crew8h = ShipVoyageConstants::treasuryCost(28800, 5);
        self::assertGreaterThan($solo2h, $crew8h);
        self::assertSame(175, $solo2h); // 80*2 + 15*1
    }

    public function testValidDurations(): void
    {
        self::assertTrue(ShipVoyageConstants::isValidDuration(7200));
        self::assertFalse(ShipVoyageConstants::isValidDuration(1000));
    }
}
