<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\WearableRarityWeightedPicker;
use App\Enum\WearableItemRarity;
use PHPUnit\Framework\TestCase;

final class WearableRarityWeightedPickerTest extends TestCase
{
    public function testHeroZeroStyleDistribution(): void
    {
        $candidates = [
            ['id' => 'c1', 'rarity' => WearableItemRarity::COMMON],
            ['id' => 'u1', 'rarity' => WearableItemRarity::UNCOMMON],
            ['id' => 'r1', 'rarity' => WearableItemRarity::RARE],
            ['id' => 'e1', 'rarity' => WearableItemRarity::EPIC],
            ['id' => 'l1', 'rarity' => WearableItemRarity::LEGENDARY],
        ];

        $counts = [
            'COMMON' => 0,
            'UNCOMMON' => 0,
            'RARE' => 0,
            'EPIC' => 0,
            'LEGENDARY' => 0,
        ];
        $samples = 5000;
        for ($i = 0; $i < $samples; ++$i) {
            $pick = WearableRarityWeightedPicker::pick(
                $candidates,
                static fn (array $c) => $c['rarity']
            );
            ++$counts[$pick['rarity']->value];
        }

        $basic = ($counts['COMMON'] + $counts['UNCOMMON']) / $samples;
        $rare = $counts['RARE'] / $samples;
        $epic = $counts['EPIC'] / $samples;
        $legendary = $counts['LEGENDARY'] / $samples;

        self::assertEqualsWithDelta(0.69, $basic, 0.05);
        self::assertEqualsWithDelta(0.25, $rare, 0.05);
        self::assertEqualsWithDelta(0.05, $epic, 0.03);
        self::assertEqualsWithDelta(0.01, $legendary, 0.015);
    }
}
