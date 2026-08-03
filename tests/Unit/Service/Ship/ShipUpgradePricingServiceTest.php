<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Ship;

use App\Service\Ship\ShipUpgradePricingService;
use PHPUnit\Framework\TestCase;

final class ShipUpgradePricingServiceTest extends TestCase
{
    public function testFirstHalfOfSkillsIsGoldOnly(): void
    {
        $l1 = ShipUpgradePricingService::legacyCost(1, 50);
        self::assertSame(150, $l1['gold']);
        self::assertSame(0, $l1['diamonds']);

        $l25 = ShipUpgradePricingService::legacyCost(25, 50);
        self::assertSame(0, $l25['diamonds']);

        $l26 = ShipUpgradePricingService::legacyCost(26, 50);
        self::assertGreaterThan(0, $l26['diamonds']);
    }

    public function testFirstHalfOfHullIsGoldOnly(): void
    {
        $l7 = ShipUpgradePricingService::legacyCost(7, 15);
        self::assertSame(0, $l7['diamonds']);

        $l8 = ShipUpgradePricingService::legacyCost(8, 15);
        self::assertGreaterThan(0, $l8['diamonds']);
    }
}
