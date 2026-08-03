<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Config\WearableItemCatalog;
use App\Enum\WearableItemType;
use PHPUnit\Framework\TestCase;

final class WearableItemCatalogTest extends TestCase
{
    public function testHasExpectedCatalogCounts(): void
    {
        $entries = WearableItemCatalog::entries();
        self::assertCount(187, $entries);

        $imageKeys = array_map(static fn (array $e) => $e['imageKey'], $entries);
        self::assertCount(187, array_unique($imageKeys), 'Every catalog entry must have a unique imageKey');

        self::assertCount(7, WearableItemCatalog::shopVariantsForType(WearableItemType::Helmet, 5));
        self::assertCount(7, WearableItemCatalog::shopVariantsForType(WearableItemType::Boots, 5));
        self::assertCount(15, WearableItemCatalog::shopVariantsForType(WearableItemType::Weapon, 5));
        self::assertCount(9, WearableItemCatalog::shopVariantsForType(WearableItemType::Armor, 5));
        self::assertCount(7, WearableItemCatalog::shopVariantsForType(WearableItemType::Amulet, 5));
        self::assertCount(7, WearableItemCatalog::shopVariantsForType(WearableItemType::Ring, 5));

        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Helmet, 15));
        self::assertCount(1, WearableItemCatalog::shopVariantsForType(WearableItemType::Boots, 15));
        self::assertCount(4, WearableItemCatalog::shopVariantsForType(WearableItemType::Weapon, 15));
        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Armor, 15));
        self::assertCount(1, WearableItemCatalog::shopVariantsForType(WearableItemType::Amulet, 15));
        self::assertCount(1, WearableItemCatalog::shopVariantsForType(WearableItemType::Ring, 15));

        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Helmet, 25));
        self::assertCount(1, WearableItemCatalog::shopVariantsForType(WearableItemType::Boots, 25));
        self::assertCount(4, WearableItemCatalog::shopVariantsForType(WearableItemType::Weapon, 25));
        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Armor, 25));
        self::assertCount(1, WearableItemCatalog::shopVariantsForType(WearableItemType::Amulet, 25));
        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Ring, 25));

        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Helmet, 30));
        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Boots, 30));
        self::assertCount(3, WearableItemCatalog::shopVariantsForType(WearableItemType::Weapon, 30));
        self::assertCount(1, WearableItemCatalog::shopVariantsForType(WearableItemType::Armor, 30));
        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Amulet, 30));
        self::assertCount(3, WearableItemCatalog::shopVariantsForType(WearableItemType::Ring, 30));

        self::assertCount(4, WearableItemCatalog::shopVariantsForType(WearableItemType::Helmet, 35));
        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Boots, 35));
        self::assertCount(7, WearableItemCatalog::shopVariantsForType(WearableItemType::Weapon, 35));
        self::assertCount(3, WearableItemCatalog::shopVariantsForType(WearableItemType::Armor, 35));
        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Amulet, 35));
        self::assertCount(3, WearableItemCatalog::shopVariantsForType(WearableItemType::Ring, 35));

        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Helmet, 40));
        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Boots, 40));
        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Weapon, 40));
        self::assertCount(1, WearableItemCatalog::shopVariantsForType(WearableItemType::Armor, 40));
        self::assertCount(1, WearableItemCatalog::shopVariantsForType(WearableItemType::Amulet, 40));
        self::assertCount(1, WearableItemCatalog::shopVariantsForType(WearableItemType::Ring, 40));

        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Helmet, 45));
        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Boots, 45));
        self::assertCount(5, WearableItemCatalog::shopVariantsForType(WearableItemType::Weapon, 45));
        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Armor, 45));
        self::assertCount(3, WearableItemCatalog::shopVariantsForType(WearableItemType::Amulet, 45));
        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Ring, 45));

        self::assertCount(3, WearableItemCatalog::shopVariantsForType(WearableItemType::Helmet, 50));
        self::assertCount(2, WearableItemCatalog::shopVariantsForType(WearableItemType::Boots, 50));
        self::assertCount(7, WearableItemCatalog::shopVariantsForType(WearableItemType::Weapon, 50));
        self::assertCount(3, WearableItemCatalog::shopVariantsForType(WearableItemType::Armor, 50));
        self::assertCount(4, WearableItemCatalog::shopVariantsForType(WearableItemType::Amulet, 50));
        self::assertCount(4, WearableItemCatalog::shopVariantsForType(WearableItemType::Ring, 50));

        self::assertGreaterThanOrEqual(1, \count(WearableItemCatalog::shopVariantsForType(WearableItemType::Helmet, 55)));
        self::assertGreaterThanOrEqual(1, \count(WearableItemCatalog::shopVariantsForType(WearableItemType::Weapon, 60)));
        self::assertGreaterThanOrEqual(1, \count(WearableItemCatalog::shopVariantsForType(WearableItemType::Armor, 70)));
        self::assertGreaterThanOrEqual(1, \count(WearableItemCatalog::shopVariantsForType(WearableItemType::Boots, 75)));
        self::assertGreaterThanOrEqual(1, \count(WearableItemCatalog::shopVariantsForType(WearableItemType::Amulet, 72)));
        self::assertGreaterThanOrEqual(1, \count(WearableItemCatalog::shopVariantsForType(WearableItemType::Ring, 70)));
    }

    public function testWeaponPoolIncludesSwordsAndDaggers(): void
    {
        $imageKeys = array_map(
            static fn (array $e) => $e['imageKey'],
            array_filter(
                WearableItemCatalog::entries(),
                static fn (array $e) => $e['type'] === WearableItemType::Weapon
            )
        );

        self::assertContains('sword_01', $imageKeys);
        self::assertContains('dagger_02', $imageKeys);
    }

    public function testTierOneEntriesDefaultToLevelOneThroughTen(): void
    {
        $tierTwoImageKeys = [
            'sword_10', 'sword_11', 'sword_12', 'dagger_07', 'dagger_08',
            'helm_08', 'helm_09', 'armor_10', 'armor_11', 'armor_12',
            'boots_08', 'boots_09', 'amulet_08', 'amulet_09', 'ring_08',
        ];

        $tierThreeImageKeys = [
            'sword_13', 'sword_14', 'sword_15', 'sword_16', 'dagger_09', 'dagger_10', 'dagger_11',
            'helm_10', 'helm_11', 'helm_12', 'armor_13', 'armor_14', 'armor_15', 'armor_16',
            'boots_10', 'boots_11', 'amulet_10', 'amulet_11', 'ring_09', 'ring_10',
        ];

        $tierFourImageKeys = [
            'sword_17', 'sword_18', 'sword_19', 'sword_20', 'dagger_12', 'dagger_13', 'dagger_14',
            'helm_13', 'helm_14', 'helm_15', 'armor_17', 'armor_18', 'armor_19', 'armor_20',
            'boots_12', 'boots_13', 'amulet_12', 'amulet_13', 'ring_11', 'ring_12',
        ];

        $tuesdayExpansionImageKeys = [
            'amulet_14', 'ring_14', 'dagger_15', 'helm_16', 'sword_21', 'armor_21',
            'ring_15', 'boots_14', 'sword_22', 'helm_17',
        ];

        $tierFiveImageKeys = [
            'sword_23', 'sword_24', 'sword_25', 'sword_26', 'dagger_16', 'dagger_17', 'dagger_18',
            'helm_18', 'helm_19', 'helm_20', 'armor_22', 'armor_23', 'armor_24', 'armor_25',
            'boots_15', 'boots_16', 'amulet_15', 'amulet_16', 'ring_16', 'ring_17',
        ];

        $wednesdayExpansionImageKeys = [
            'sword_27', 'armor_26', 'dagger_19', 'amulet_17', 'helm_21', 'ring_18',
            'ring_19', 'amulet_18', 'boots_17', 'dagger_20',
        ];

        $tierSixImageKeys = [
            'sword_28', 'sword_29', 'sword_30', 'sword_31', 'sword_32',
            'dagger_21', 'dagger_22', 'dagger_23', 'dagger_24',
            'helm_22', 'helm_23', 'helm_24', 'helm_25',
            'armor_27', 'armor_28', 'armor_29', 'armor_30', 'armor_31',
            'boots_18', 'boots_19', 'boots_20', 'boots_21',
            'amulet_19', 'amulet_20', 'amulet_21', 'amulet_22',
            'ring_20', 'ring_21', 'ring_22', 'ring_23',
        ];

        $thursdayCollectorImageKeys = [
            'helm_26', 'amulet_23', 'dagger_25', 'amulet_24', 'amulet_25',
            'ring_24', 'amulet_26', 'helm_27', 'ring_25', 'armor_32',
        ];

        foreach (WearableItemCatalog::entries() as $entry) {
            if (\in_array($entry['imageKey'], $thursdayCollectorImageKeys, true)) {
                self::assertGreaterThanOrEqual(60, $entry['minLevel']);
                self::assertLessThanOrEqual(75, $entry['maxLevel']);

                continue;
            }

            if (\in_array($entry['imageKey'], $tierSixImageKeys, true)) {
                self::assertGreaterThanOrEqual(50, $entry['minLevel']);
                self::assertLessThanOrEqual(75, $entry['maxLevel']);

                continue;
            }

            if (\in_array($entry['imageKey'], $wednesdayExpansionImageKeys, true)) {
                self::assertGreaterThanOrEqual(40, $entry['minLevel']);

                continue;
            }

            if (\in_array($entry['imageKey'], $tierFiveImageKeys, true)) {
                self::assertGreaterThanOrEqual(35, $entry['minLevel']);
                self::assertLessThanOrEqual(50, $entry['maxLevel']);

                continue;
            }

            if (\in_array($entry['imageKey'], $tuesdayExpansionImageKeys, true)) {
                self::assertGreaterThanOrEqual(25, $entry['minLevel']);

                continue;
            }

            if (\in_array($entry['imageKey'], $tierTwoImageKeys, true)) {
                self::assertGreaterThanOrEqual(10, $entry['minLevel']);
                self::assertLessThanOrEqual(15, $entry['maxLevel']);

                continue;
            }

            if (\in_array($entry['imageKey'], $tierThreeImageKeys, true)) {
                self::assertGreaterThanOrEqual(15, $entry['minLevel']);
                self::assertLessThanOrEqual(25, $entry['maxLevel']);

                continue;
            }

            if (\in_array($entry['imageKey'], $tierFourImageKeys, true)) {
                self::assertGreaterThanOrEqual(25, $entry['minLevel']);
                self::assertLessThanOrEqual(35, $entry['maxLevel']);

                continue;
            }

            self::assertSame(1, $entry['minLevel']);
            self::assertSame(10, $entry['maxLevel']);
        }
    }

    public function testTierFourPoolIncludesGhostShipSabre(): void
    {
        $imageKeys = array_map(
            static fn (array $e) => $e['imageKey'],
            WearableItemCatalog::shopVariantsForType(WearableItemType::Weapon, 25)
        );

        self::assertContains('sword_17', $imageKeys);
    }

    public function testRandomForTypeAtHighLevelUsesDefaultTen(): void
    {
        $pick = WearableItemCatalog::randomForType(WearableItemType::Helmet);

        self::assertArrayHasKey('nameKey', $pick);
        self::assertArrayHasKey('imageKey', $pick);
    }
}
