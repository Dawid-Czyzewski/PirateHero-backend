<?php

declare(strict_types=1);

namespace App\Config;

use App\Domain\WearableRarityWeightedPicker;
use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;


final class WearableItemCatalog
{
    /**
     * @return list<array{
     *   publicCode: string,
     *   nameKey: string,
     *   imageKey: string,
     *   type: WearableItemType,
     *   rarity: WearableItemRarity,
     *   minLevel: int,
     *   maxLevel: int,
     * }>
     */
    public static function entries(): array
    {
        $r = static fn (string $c) => WearableItemRarity::from(strtoupper($c));

        return [
            self::entry('catalog-helm-01', 'items.captainTricorn', 'helm_01', WearableItemType::Helmet, $r('uncommon')),
            self::entry('catalog-helm-02', 'items.ironBascinet', 'helm_02', WearableItemType::Helmet, $r('rare')),
            self::entry('catalog-helm-03', 'items.leatherHood', 'helm_03', WearableItemType::Helmet, $r('common')),
            self::entry('catalog-helm-04', 'items.goldenCrown', 'helm_04', WearableItemType::Helmet, $r('epic')),
            self::entry('catalog-helm-05', 'items.stormHornedHelm', 'helm_05', WearableItemType::Helmet, $r('legendary')),
            self::entry('catalog-helm-06', 'items.noviceCap', 'helm_06', WearableItemType::Helmet, $r('common')),
            self::entry('catalog-helm-07', 'items.oldTricornHat', 'helm_07', WearableItemType::Helmet, $r('uncommon')),

            self::entry('catalog-boots-01', 'items.deckSlippers', 'boots_01', WearableItemType::Boots, $r('common')),
            self::entry('catalog-boots-02', 'items.sailorBoots', 'boots_02', WearableItemType::Boots, $r('uncommon')),
            self::entry('catalog-boots-03', 'items.corsairBoots', 'boots_03', WearableItemType::Boots, $r('uncommon')),
            self::entry('catalog-boots-04', 'items.ironGreaves', 'boots_04', WearableItemType::Boots, $r('rare')),
            self::entry('catalog-boots-05', 'items.swashbucklerBoots', 'boots_05', WearableItemType::Boots, $r('epic')),
            self::entry('catalog-boots-06', 'items.sailorShoes', 'boots_06', WearableItemType::Boots, $r('common')),
            self::entry('catalog-boots-07', 'items.wetFootwear', 'boots_07', WearableItemType::Boots, $r('uncommon')),

            self::entry('catalog-sword-01', 'items.rustyCutlass', 'sword_01', WearableItemType::Weapon, $r('common')),
            self::entry('catalog-sword-02', 'items.steelSaber', 'sword_02', WearableItemType::Weapon, $r('uncommon')),
            self::entry('catalog-sword-03', 'items.admiralBlade', 'sword_03', WearableItemType::Weapon, $r('rare')),

            self::entry('catalog-dagger-01', 'items.skullDagger', 'dagger_01', WearableItemType::Weapon, $r('uncommon')),
            self::entry('catalog-dagger-02', 'items.krakenStiletto', 'dagger_02', WearableItemType::Weapon, $r('rare')),
            self::entry('catalog-sword-04', 'items.woodenRapier', 'sword_04', WearableItemType::Weapon, $r('common')),
            self::entry('catalog-sword-05', 'items.oldPirateSaber', 'sword_05', WearableItemType::Weapon, $r('uncommon')),
            self::entry('catalog-dagger-03', 'items.shortDeckKnife', 'dagger_03', WearableItemType::Weapon, $r('common')),
            self::entry('catalog-sword-06', 'items.boardingCutlass', 'sword_06', WearableItemType::Weapon, $r('common')),
            self::entry('catalog-sword-07', 'items.corsairSaber', 'sword_07', WearableItemType::Weapon, $r('uncommon')),
            self::entry('catalog-sword-08', 'items.navySaber', 'sword_08', WearableItemType::Weapon, $r('rare')),
            self::entry('catalog-sword-09', 'items.boneCutlass', 'sword_09', WearableItemType::Weapon, $r('uncommon')),
            self::entry('catalog-dagger-04', 'items.rustyDagger', 'dagger_04', WearableItemType::Weapon, $r('common')),
            self::entry('catalog-dagger-05', 'items.corsairDirk', 'dagger_05', WearableItemType::Weapon, $r('uncommon')),
            self::entry('catalog-dagger-06', 'items.boneStiletto', 'dagger_06', WearableItemType::Weapon, $r('rare')),

            self::entry('catalog-armor-01', 'items.leatherJerkin', 'armor_01', WearableItemType::Armor, $r('common')),
            self::entry('catalog-armor-02', 'items.sailorCoat', 'armor_02', WearableItemType::Armor, $r('uncommon')),
            self::entry('catalog-armor-03', 'items.captainCloak', 'armor_03', WearableItemType::Armor, $r('uncommon')),
            self::entry('catalog-armor-04', 'items.krakenScaleMail', 'armor_04', WearableItemType::Armor, $r('rare')),
            self::entry('catalog-armor-05', 'items.plateVest', 'armor_05', WearableItemType::Armor, $r('epic')),
            self::entry('catalog-armor-06', 'items.sailorVest', 'armor_06', WearableItemType::Armor, $r('common')),
            self::entry('catalog-armor-07', 'items.leatherJacket', 'armor_07', WearableItemType::Armor, $r('common')),
            self::entry('catalog-armor-08', 'items.deckGloves', 'armor_08', WearableItemType::Armor, $r('uncommon')),
            self::entry('catalog-armor-09', 'items.wornGloves', 'armor_09', WearableItemType::Armor, $r('common')),

            self::entry('catalog-amulet-01', 'items.compassCharm', 'amulet_01', WearableItemType::Amulet, $r('uncommon')),
            self::entry('catalog-amulet-02', 'items.krakenTooth', 'amulet_02', WearableItemType::Amulet, $r('rare')),
            self::entry('catalog-amulet-03', 'items.stormEye', 'amulet_03', WearableItemType::Amulet, $r('rare')),
            self::entry('catalog-amulet-04', 'items.rumVialCharm', 'amulet_04', WearableItemType::Amulet, $r('common')),
            self::entry('catalog-amulet-05', 'items.pearlPendant', 'amulet_05', WearableItemType::Amulet, $r('epic')),
            self::entry('catalog-amulet-06', 'items.woodenAmulet', 'amulet_06', WearableItemType::Amulet, $r('common')),
            self::entry('catalog-amulet-07', 'items.oldMedallion', 'amulet_07', WearableItemType::Amulet, $r('uncommon')),

            self::entry('catalog-ring-01', 'items.brassBand', 'ring_01', WearableItemType::Ring, $r('common')),
            self::entry('catalog-ring-02', 'items.goldSignet', 'ring_02', WearableItemType::Ring, $r('uncommon')),
            self::entry('catalog-ring-03', 'items.pirateEarring', 'ring_03', WearableItemType::Ring, $r('uncommon')),
            self::entry('catalog-ring-04', 'items.rubyRing', 'ring_04', WearableItemType::Ring, $r('rare')),
            self::entry('catalog-ring-05', 'items.serpentCoil', 'ring_05', WearableItemType::Ring, $r('epic')),
            self::entry('catalog-ring-06', 'items.copperRing', 'ring_06', WearableItemType::Ring, $r('common')),
            self::entry('catalog-ring-07', 'items.boneRing', 'ring_07', WearableItemType::Ring, $r('uncommon')),

            self::entry('catalog-sword-10', 'items.stormbreakerBlade', 'sword_10', WearableItemType::Weapon, $r('rare'), 10, 11),
            self::entry('catalog-sword-11', 'items.reefFangSabre', 'sword_11', WearableItemType::Weapon, $r('epic'), 12, 13),
            self::entry('catalog-sword-12', 'items.abyssLordCutlass', 'sword_12', WearableItemType::Weapon, $r('legendary'), 14, 15),
            self::entry('catalog-dagger-07', 'items.tideRipperKnife', 'dagger_07', WearableItemType::Weapon, $r('uncommon'), 10, 11),
            self::entry('catalog-dagger-08', 'items.deepwaterStiletto', 'dagger_08', WearableItemType::Weapon, $r('rare'), 13, 15),
            self::entry('catalog-helm-08', 'items.fortressBascinet', 'helm_08', WearableItemType::Helmet, $r('rare'), 10, 12),
            self::entry('catalog-helm-09', 'items.tempestAdmiralHat', 'helm_09', WearableItemType::Helmet, $r('epic'), 13, 15),
            self::entry('catalog-armor-10', 'items.reefguardVest', 'armor_10', WearableItemType::Armor, $r('uncommon'), 10, 11),
            self::entry('catalog-armor-11', 'items.krakenLordMail', 'armor_11', WearableItemType::Armor, $r('rare'), 12, 13),
            self::entry('catalog-armor-12', 'items.volcanicPlatecoat', 'armor_12', WearableItemType::Armor, $r('legendary'), 14, 15),
            self::entry('catalog-boots-08', 'items.stoneDockBoots', 'boots_08', WearableItemType::Boots, $r('uncommon'), 10, 12),
            self::entry('catalog-boots-09', 'items.abyssWalkerGreaves', 'boots_09', WearableItemType::Boots, $r('epic'), 13, 15),
            self::entry('catalog-amulet-08', 'items.abyssPearlCharm', 'amulet_08', WearableItemType::Amulet, $r('rare'), 10, 12),
            self::entry('catalog-amulet-09', 'items.volcanoHeartPendant', 'amulet_09', WearableItemType::Amulet, $r('epic'), 13, 15),
            self::entry('catalog-ring-08', 'items.treasureHunterSignet', 'ring_08', WearableItemType::Ring, $r('rare'), 10, 15),

            self::entry('catalog-sword-13', 'items.krakenBlade', 'sword_13', WearableItemType::Weapon, $r('rare'), 15, 17),
            self::entry('catalog-sword-14', 'items.blackFleetSabre', 'sword_14', WearableItemType::Weapon, $r('epic'), 18, 20),
            self::entry('catalog-sword-15', 'items.abyssReaver', 'sword_15', WearableItemType::Weapon, $r('epic'), 21, 23),
            self::entry('catalog-sword-16', 'items.cursedAdmiralCutlass', 'sword_16', WearableItemType::Weapon, $r('legendary'), 23, 25),
            self::entry('catalog-dagger-09', 'items.reefStriker', 'dagger_09', WearableItemType::Weapon, $r('uncommon'), 15, 17),
            self::entry('catalog-dagger-10', 'items.cursedTreasureKnife', 'dagger_10', WearableItemType::Weapon, $r('rare'), 18, 20),
            self::entry('catalog-dagger-11', 'items.oceanWarriorBlade', 'dagger_11', WearableItemType::Weapon, $r('epic'), 22, 25),
            self::entry('catalog-helm-10', 'items.ironCorsairHelm', 'helm_10', WearableItemType::Helmet, $r('rare'), 15, 17),
            self::entry('catalog-helm-11', 'items.blackbeardFortressHelm', 'helm_11', WearableItemType::Helmet, $r('epic'), 19, 22),
            self::entry('catalog-helm-12', 'items.krakenCrown', 'helm_12', WearableItemType::Helmet, $r('legendary'), 23, 25),
            self::entry('catalog-armor-13', 'items.boardingCaptainCoat', 'armor_13', WearableItemType::Armor, $r('rare'), 15, 17),
            self::entry('catalog-armor-14', 'items.cursedTreasureVest', 'armor_14', WearableItemType::Armor, $r('rare'), 18, 20),
            self::entry('catalog-armor-15', 'items.oceanWarriorMail', 'armor_15', WearableItemType::Armor, $r('epic'), 21, 23),
            self::entry('catalog-armor-16', 'items.blackFleetPlate', 'armor_16', WearableItemType::Armor, $r('legendary'), 23, 25),
            self::entry('catalog-boots-10', 'items.deepSeaTreads', 'boots_10', WearableItemType::Boots, $r('rare'), 16, 20),
            self::entry('catalog-boots-11', 'items.krakenTractionGreaves', 'boots_11', WearableItemType::Boots, $r('epic'), 21, 25),
            self::entry('catalog-amulet-10', 'items.pirateArtifactCharm', 'amulet_10', WearableItemType::Amulet, $r('rare'), 16, 20),
            self::entry('catalog-amulet-11', 'items.cursedDoubloonPendant', 'amulet_11', WearableItemType::Amulet, $r('epic'), 21, 25),
            self::entry('catalog-ring-09', 'items.abyssRing', 'ring_09', WearableItemType::Ring, $r('rare'), 16, 20),
            self::entry('catalog-ring-10', 'items.blackFleetSignet', 'ring_10', WearableItemType::Ring, $r('legendary'), 22, 25),

            self::entry('catalog-sword-17', 'items.ghostShipSabre', 'sword_17', WearableItemType::Weapon, $r('rare'), 25, 27),
            self::entry('catalog-sword-18', 'items.phantomAdmiralBlade', 'sword_18', WearableItemType::Weapon, $r('epic'), 28, 31),
            self::entry('catalog-sword-19', 'items.poseidonTridentSabre', 'sword_19', WearableItemType::Weapon, $r('epic'), 32, 33),
            self::entry('catalog-sword-20', 'items.corsairKingsCutlass', 'sword_20', WearableItemType::Weapon, $r('legendary'), 34, 35),
            self::entry('catalog-dagger-12', 'items.spiritTideKnife', 'dagger_12', WearableItemType::Weapon, $r('uncommon'), 25, 27),
            self::entry('catalog-dagger-13', 'items.hauntedDoubloonStiletto', 'dagger_13', WearableItemType::Weapon, $r('rare'), 28, 31),
            self::entry('catalog-dagger-14', 'items.abyssCorsairDagger', 'dagger_14', WearableItemType::Weapon, $r('epic'), 32, 35),
            self::entry('catalog-helm-13', 'items.ghostCaptainHat', 'helm_13', WearableItemType::Helmet, $r('rare'), 25, 28),
            self::entry('catalog-helm-14', 'items.hauntedGalleonHelm', 'helm_14', WearableItemType::Helmet, $r('epic'), 29, 32),
            self::entry('catalog-helm-15', 'items.poseidonCrown', 'helm_15', WearableItemType::Helmet, $r('legendary'), 33, 35),
            self::entry('catalog-armor-17', 'items.spectralDeckcoat', 'armor_17', WearableItemType::Armor, $r('rare'), 25, 27),
            self::entry('catalog-armor-18', 'items.cursedAdmiralCoat', 'armor_18', WearableItemType::Armor, $r('rare'), 28, 30),
            self::entry('catalog-armor-19', 'items.ghostFleetMail', 'armor_19', WearableItemType::Armor, $r('epic'), 31, 33),
            self::entry('catalog-armor-20', 'items.oceanRelicPlate', 'armor_20', WearableItemType::Armor, $r('legendary'), 34, 35),
            self::entry('catalog-boots-12', 'items.phantomDeckBoots', 'boots_12', WearableItemType::Boots, $r('rare'), 26, 29),
            self::entry('catalog-boots-13', 'items.poseidonTideGreaves', 'boots_13', WearableItemType::Boots, $r('epic'), 30, 35),
            self::entry('catalog-amulet-12', 'items.hauntedCompassCharm', 'amulet_12', WearableItemType::Amulet, $r('rare'), 26, 30),
            self::entry('catalog-amulet-13', 'items.poseidonRelicPendant', 'amulet_13', WearableItemType::Amulet, $r('epic'), 31, 35),
            self::entry('catalog-ring-11', 'items.corsairRelicSignet', 'ring_11', WearableItemType::Ring, $r('rare'), 27, 31),
            self::entry('catalog-ring-12', 'items.cursedAdmiralRing', 'ring_12', WearableItemType::Ring, $r('legendary'), 32, 35),

            self::entry('catalog-amulet-14', 'items.poseidonTideCharm', 'amulet_14', WearableItemType::Amulet, $r('legendary'), 30, 35),
            self::entry('catalog-ring-14', 'items.atlantisSignet', 'ring_14', WearableItemType::Ring, $r('epic'), 28, 35),
            self::entry('catalog-dagger-15', 'items.cursedRelicDagger', 'dagger_15', WearableItemType::Weapon, $r('legendary'), 30, 35),
            self::entry('catalog-helm-16', 'items.cursedRelicHelm', 'helm_16', WearableItemType::Helmet, $r('epic'), 28, 35),
            self::entry('catalog-sword-21', 'items.atlantisTreasureBlade', 'sword_21', WearableItemType::Weapon, $r('legendary'), 32, 35),
            self::entry('catalog-armor-21', 'items.atlantisRelicMail', 'armor_21', WearableItemType::Armor, $r('legendary'), 32, 35),
            self::entry('catalog-ring-15', 'items.pirateTrophyRing', 'ring_15', WearableItemType::Ring, $r('rare'), 25, 35),
            self::entry('catalog-boots-14', 'items.pirateTrophyBoots', 'boots_14', WearableItemType::Boots, $r('epic'), 28, 35),
            self::entry('catalog-sword-22', 'items.legendarySouvenirSabre', 'sword_22', WearableItemType::Weapon, $r('legendary'), 34, 35),
            self::entry('catalog-helm-17', 'items.legendarySouvenirHelm', 'helm_17', WearableItemType::Helmet, $r('legendary'), 33, 35),

            self::entry('catalog-sword-23', 'items.atlantisDeepSabre', 'sword_23', WearableItemType::Weapon, $r('rare'), 35, 37),
            self::entry('catalog-sword-24', 'items.titanSlayerBlade', 'sword_24', WearableItemType::Weapon, $r('epic'), 38, 42),
            self::entry('catalog-sword-25', 'items.immortalCaptainCutlass', 'sword_25', WearableItemType::Weapon, $r('epic'), 43, 47),
            self::entry('catalog-sword-26', 'items.abyssEmperorBlade', 'sword_26', WearableItemType::Weapon, $r('legendary'), 48, 50),
            self::entry('catalog-dagger-16', 'items.krakenFangKnife', 'dagger_16', WearableItemType::Weapon, $r('uncommon'), 35, 38),
            self::entry('catalog-dagger-17', 'items.relicHunterStiletto', 'dagger_17', WearableItemType::Weapon, $r('rare'), 39, 43),
            self::entry('catalog-dagger-18', 'items.shadowTideDagger', 'dagger_18', WearableItemType::Weapon, $r('epic'), 44, 50),
            self::entry('catalog-helm-18', 'items.abyssSentinelHelm', 'helm_18', WearableItemType::Helmet, $r('rare'), 35, 39),
            self::entry('catalog-helm-19', 'items.titanGuardHelm', 'helm_19', WearableItemType::Helmet, $r('epic'), 40, 45),
            self::entry('catalog-helm-20', 'items.oceanEmperorCrown', 'helm_20', WearableItemType::Helmet, $r('legendary'), 46, 50),
            self::entry('catalog-armor-22', 'items.deepRelicMail', 'armor_22', WearableItemType::Armor, $r('rare'), 35, 38),
            self::entry('catalog-armor-23', 'items.atlantisGuardPlate', 'armor_23', WearableItemType::Armor, $r('rare'), 39, 42),
            self::entry('catalog-armor-24', 'items.krakenLordPlate', 'armor_24', WearableItemType::Armor, $r('epic'), 43, 47),
            self::entry('catalog-armor-25', 'items.immortalAdmiralCoat', 'armor_25', WearableItemType::Armor, $r('legendary'), 48, 50),
            self::entry('catalog-boots-15', 'items.abyssStrideBoots', 'boots_15', WearableItemType::Boots, $r('rare'), 36, 42),
            self::entry('catalog-boots-16', 'items.titanWalkGreaves', 'boots_16', WearableItemType::Boots, $r('epic'), 44, 50),
            self::entry('catalog-amulet-15', 'items.deepOceanCharm', 'amulet_15', WearableItemType::Amulet, $r('rare'), 37, 43),
            self::entry('catalog-amulet-16', 'items.emperorRelicPendant', 'amulet_16', WearableItemType::Amulet, $r('epic'), 45, 50),
            self::entry('catalog-ring-16', 'items.atlantisSealRing', 'ring_16', WearableItemType::Ring, $r('rare'), 38, 44),
            self::entry('catalog-ring-17', 'items.titanSignet', 'ring_17', WearableItemType::Ring, $r('legendary'), 46, 50),

            self::entry('catalog-sword-27', 'items.atlantisRelicBlade', 'sword_27', WearableItemType::Weapon, $r('legendary'), 42, 50),
            self::entry('catalog-armor-26', 'items.sunkenCityMail', 'armor_26', WearableItemType::Armor, $r('legendary'), 44, 50),
            self::entry('catalog-dagger-19', 'items.krakenEyeDagger', 'dagger_19', WearableItemType::Weapon, $r('legendary'), 43, 50),
            self::entry('catalog-amulet-17', 'items.krakenHeartCharm', 'amulet_17', WearableItemType::Amulet, $r('legendary'), 45, 50),
            self::entry('catalog-helm-21', 'items.blackbeardTreasureHat', 'helm_21', WearableItemType::Helmet, $r('epic'), 40, 50),
            self::entry('catalog-ring-18', 'items.blackbeardLootRing', 'ring_18', WearableItemType::Ring, $r('legendary'), 42, 50),
            self::entry('catalog-ring-19', 'items.cursedDoubloonRing', 'ring_19', WearableItemType::Ring, $r('epic'), 41, 50),
            self::entry('catalog-amulet-18', 'items.cursedCoinPendant', 'amulet_18', WearableItemType::Amulet, $r('legendary'), 43, 50),
            self::entry('catalog-boots-17', 'items.ancientMapBoots', 'boots_17', WearableItemType::Boots, $r('epic'), 40, 48),
            self::entry('catalog-dagger-20', 'items.chartBladeDagger', 'dagger_20', WearableItemType::Weapon, $r('epic'), 42, 50),

            self::entry('catalog-sword-28', 'items.atlantisEmperorSabre', 'sword_28', WearableItemType::Weapon, $r('epic'), 50, 55),
            self::entry('catalog-sword-29', 'items.leviathanFangBlade', 'sword_29', WearableItemType::Weapon, $r('epic'), 56, 62),
            self::entry('catalog-sword-30', 'items.stormCrownCutlass', 'sword_30', WearableItemType::Weapon, $r('legendary'), 58, 65),
            self::entry('catalog-sword-31', 'items.volcanoForgeSabre', 'sword_31', WearableItemType::Weapon, $r('epic'), 63, 69),
            self::entry('catalog-sword-32', 'items.lostFleetAdmiralBlade', 'sword_32', WearableItemType::Weapon, $r('legendary'), 68, 75),
            self::entry('catalog-dagger-21', 'items.oceanRelicDirk', 'dagger_21', WearableItemType::Weapon, $r('rare'), 50, 56),
            self::entry('catalog-dagger-22', 'items.iceSeaStiletto', 'dagger_22', WearableItemType::Weapon, $r('epic'), 57, 64),
            self::entry('catalog-dagger-23', 'items.pirateArtifactKnife', 'dagger_23', WearableItemType::Weapon, $r('epic'), 62, 70),
            self::entry('catalog-dagger-24', 'items.leviathanSpineDagger', 'dagger_24', WearableItemType::Weapon, $r('legendary'), 68, 75),
            self::entry('catalog-helm-22', 'items.stormCrownHelm', 'helm_22', WearableItemType::Helmet, $r('epic'), 50, 58),
            self::entry('catalog-helm-23', 'items.atlantisEmperorCrown', 'helm_23', WearableItemType::Helmet, $r('legendary'), 56, 65),
            self::entry('catalog-helm-24', 'items.volcanoWarHelm', 'helm_24', WearableItemType::Helmet, $r('epic'), 60, 70),
            self::entry('catalog-helm-25', 'items.lostFleetCaptainHat', 'helm_25', WearableItemType::Helmet, $r('legendary'), 68, 75),
            self::entry('catalog-armor-27', 'items.oceanRelicCuirass', 'armor_27', WearableItemType::Armor, $r('epic'), 50, 56),
            self::entry('catalog-armor-28', 'items.iceSeaMail', 'armor_28', WearableItemType::Armor, $r('epic'), 55, 63),
            self::entry('catalog-armor-29', 'items.volcanoForgeCoat', 'armor_29', WearableItemType::Armor, $r('legendary'), 60, 68),
            self::entry('catalog-armor-30', 'items.pirateArtifactVest', 'armor_30', WearableItemType::Armor, $r('epic'), 64, 72),
            self::entry('catalog-armor-31', 'items.lostFleetWarPlate', 'armor_31', WearableItemType::Armor, $r('legendary'), 70, 75),
            self::entry('catalog-boots-18', 'items.stormStrideBoots', 'boots_18', WearableItemType::Boots, $r('epic'), 50, 58),
            self::entry('catalog-boots-19', 'items.iceSeaGreaves', 'boots_19', WearableItemType::Boots, $r('epic'), 56, 65),
            self::entry('catalog-boots-20', 'items.volcanoAshBoots', 'boots_20', WearableItemType::Boots, $r('legendary'), 62, 70),
            self::entry('catalog-boots-21', 'items.lostFleetTreads', 'boots_21', WearableItemType::Boots, $r('legendary'), 68, 75),
            self::entry('catalog-amulet-19', 'items.oceanRelicCharm', 'amulet_19', WearableItemType::Amulet, $r('epic'), 50, 58),
            self::entry('catalog-amulet-20', 'items.stormCrownPendant', 'amulet_20', WearableItemType::Amulet, $r('epic'), 56, 64),
            self::entry('catalog-amulet-21', 'items.leviathanHeartAmulet', 'amulet_21', WearableItemType::Amulet, $r('legendary'), 62, 70),
            self::entry('catalog-amulet-22', 'items.atlantisEmperorSeal', 'amulet_22', WearableItemType::Amulet, $r('legendary'), 68, 75),
            self::entry('catalog-ring-20', 'items.iceSeaBand', 'ring_20', WearableItemType::Ring, $r('epic'), 50, 58),
            self::entry('catalog-ring-21', 'items.volcanoEmberRing', 'ring_21', WearableItemType::Ring, $r('epic'), 56, 64),
            self::entry('catalog-ring-22', 'items.pirateArtifactSignet', 'ring_22', WearableItemType::Ring, $r('legendary'), 62, 70),
            self::entry('catalog-ring-23', 'items.lostFleetSealRing', 'ring_23', WearableItemType::Ring, $r('legendary'), 68, 75),

            self::entry('catalog-helm-26', 'items.poseidonCrownHelm', 'helm_26', WearableItemType::Helmet, $r('legendary'), 65, 75),
            self::entry('catalog-amulet-23', 'items.atlantisScepterCharm', 'amulet_23', WearableItemType::Amulet, $r('legendary'), 65, 75),
            self::entry('catalog-dagger-25', 'items.leviathanToothBlade', 'dagger_25', WearableItemType::Weapon, $r('legendary'), 65, 75),
            self::entry('catalog-amulet-24', 'items.krakenEyeRelic', 'amulet_24', WearableItemType::Amulet, $r('legendary'), 66, 75),
            self::entry('catalog-amulet-25', 'items.explorerCompassCharm', 'amulet_25', WearableItemType::Amulet, $r('legendary'), 60, 72),
            self::entry('catalog-ring-24', 'items.stormRelicRing', 'ring_24', WearableItemType::Ring, $r('legendary'), 62, 75),
            self::entry('catalog-amulet-26', 'items.admiralMedallion', 'amulet_26', WearableItemType::Amulet, $r('legendary'), 64, 75),
            self::entry('catalog-helm-27', 'items.corsairMaskHelm', 'helm_27', WearableItemType::Helmet, $r('legendary'), 63, 75),
            self::entry('catalog-ring-25', 'items.abyssDepthRing', 'ring_25', WearableItemType::Ring, $r('legendary'), 66, 75),
            self::entry('catalog-armor-32', 'items.oceanLordPlate', 'armor_32', WearableItemType::Armor, $r('legendary'), 68, 75),
        ];
    }

    /**
     * @return list<array{nameKey: string, imageKey: string, rarity: WearableItemRarity}>
     */
    public static function shopVariantsForType(WearableItemType $type, int $playerLevel = 10): array
    {
        $variants = [];
        $level = max(1, $playerLevel);
        foreach (self::entries() as $entry) {
            if ($entry['type'] !== $type) {
                continue;
            }
            if ($level < $entry['minLevel'] || $level > $entry['maxLevel']) {
                continue;
            }
            $variants[] = [
                'nameKey' => $entry['nameKey'],
                'imageKey' => $entry['imageKey'],
                'rarity' => $entry['rarity'],
            ];
        }

        return $variants;
    }

    /**
     * @return array{nameKey: string, imageKey: string, rarity: WearableItemRarity, type: WearableItemType}
     */
    public static function randomForType(WearableItemType $type, ?WearableItemRarity $rarity = null, int $playerLevel = 10): array
    {
        $level = max(1, $playerLevel);
        $pool = array_values(array_filter(
            self::entries(),
            static fn (array $e) => $e['type'] === $type
                && $level >= $e['minLevel']
                && $level <= $e['maxLevel']
                && ($rarity === null || $e['rarity'] === $rarity)
        ));

        if ($pool === [] && $rarity !== null) {
            return self::randomForType($type, null, $playerLevel);
        }

        if ($pool === []) {
            throw new \LogicException('No catalog entries for type '.$type->value);
        }

        $pick = WearableRarityWeightedPicker::pick(
            $pool,
            static fn (array $e) => $e['rarity']
        );

        return [
            'nameKey' => $pick['nameKey'],
            'imageKey' => $pick['imageKey'],
            'rarity' => $pick['rarity'],
            'type' => $pick['type'],
        ];
    }

    public static function displayNameForKey(string $nameKey): string
    {
        $leaf = substr($nameKey, (int) strrpos($nameKey, '.') + 1);

        return $leaf !== '' ? $leaf : $nameKey;
    }

    /**
     * @return array{
     *   publicCode: string,
     *   nameKey: string,
     *   imageKey: string,
     *   type: WearableItemType,
     *   rarity: WearableItemRarity,
     *   minLevel: int,
     *   maxLevel: int,
     * }
     */
    private static function entry(
        string $publicCode,
        string $nameKey,
        string $imageKey,
        WearableItemType $type,
        WearableItemRarity $rarity,
        int $minLevel = 1,
        int $maxLevel = 10,
    ): array {
        return [
            'publicCode' => $publicCode,
            'nameKey' => $nameKey,
            'imageKey' => $imageKey,
            'type' => $type,
            'rarity' => $rarity,
            'minLevel' => $minLevel,
            'maxLevel' => $maxLevel,
        ];
    }
}
