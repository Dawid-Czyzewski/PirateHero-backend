<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\WearableItemType;
use PHPUnit\Framework\TestCase;

final class WearableItemTypeTest extends TestCase
{
    public function testFromLegacyOrSelfMapsOldApiTypes(): void
    {
        $this->assertSame(WearableItemType::Helmet, WearableItemType::fromLegacyOrSelf('HEAD'));
        $this->assertSame(WearableItemType::Weapon, WearableItemType::fromLegacyOrSelf('HANDS'));
        $this->assertSame(WearableItemType::Armor, WearableItemType::fromLegacyOrSelf('SHIRT'));
        $this->assertSame(WearableItemType::Armor, WearableItemType::fromLegacyOrSelf('PANTS'));
        $this->assertSame(WearableItemType::Boots, WearableItemType::fromLegacyOrSelf('SHOES'));
    }

    public function testFromLegacyOrSelfAcceptsCurrentValues(): void
    {
        $this->assertSame(WearableItemType::Helmet, WearableItemType::fromLegacyOrSelf('helmet'));
        $this->assertSame(WearableItemType::Amulet, WearableItemType::fromLegacyOrSelf('amulet'));
    }

    public function testOrderedCasesHasSixSlots(): void
    {
        $this->assertCount(6, WearableItemType::orderedCases());
    }
}
