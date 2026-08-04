<?php

declare(strict_types=1);

namespace App\Progression;

use App\Enum\TitleUnlockType;

final class PlayerTitleCatalog
{
    /**
     * @return list<TitleDef>
     */
    public static function definitions(): array
    {
        $rows = [
            self::row('rookie', TitleUnlockType::GAME_START, null, null, 1),
            self::row('crypt_hunter', TitleUnlockType::DUNGEON_COMPLETED, null, 'krypta', 2),
            self::row('kraken_slayer', TitleUnlockType::DUNGEON_COMPLETED, null, 'kraken', 3),
            self::row('veteran', TitleUnlockType::LEVEL_REACHED, 25, null, 4),
            self::row('rich_captain', TitleUnlockType::GOLD_BALANCE, 10000, null, 5),
            self::row('fortress_raider', TitleUnlockType::DUNGEON_COMPLETED, null, 'forteca', 6),
            self::row('volcanic_conqueror', TitleUnlockType::DUNGEON_COMPLETED, null, 'wulkan', 7),
            self::row('poseidon_champion', TitleUnlockType::DUNGEON_COMPLETED, null, 'palac', 8),
            self::row('collector', TitleUnlockType::ITEMS_COLLECTED, 10, null, 9),
            self::row('treasure_hunter', TitleUnlockType::ITEMS_COLLECTED, 100, null, 10),
            self::row('dungeon_master', TitleUnlockType::ALL_DUNGEONS_COMPLETED, null, null, 11),
            self::row('veteran_collector', TitleUnlockType::RARE_EQUIPMENT_FULL, null, null, 12),
            self::row('legendary_collector', TitleUnlockType::ITEMS_COLLECTED, 100, null, 13),
            self::row('sea_legend', TitleUnlockType::ALL_DUNGEONS_AND_LEVEL, 50, null, 14),
            self::row('master_collector', TitleUnlockType::ITEMS_COLLECTED, 85, null, 15),
            self::row('legendary_hunter', TitleUnlockType::LEGENDARY_ITEMS_COLLECTED, 5, null, 16),
            self::row('veteran_captain', TitleUnlockType::LEVEL_REACHED, 35, null, 17),
            self::row('undead_slayer', TitleUnlockType::DUNGEON_COMPLETED, null, 'krypta', 18),
            self::row('beast_hunter', TitleUnlockType::BESTIARY_COMPLETE, 50, null, 19),
            self::row('black_corsair', TitleUnlockType::FIGHTS_WON, 250, null, 20),
            self::row('elite_captain', TitleUnlockType::LEVEL_REACHED, 50, null, 21),
            self::row('fortress_lord', TitleUnlockType::DUNGEON_COMPLETED, null, 'forteca', 22),
            self::row('atlantis_guardian', TitleUnlockType::DUNGEON_COMPLETED, null, 'palac', 23),
            self::row('legend_collector', TitleUnlockType::LEGENDARY_ITEMS_COLLECTED, 15, null, 24),
            self::row('ocean_master', TitleUnlockType::FIGHTS_WON, 1000, null, 25),
            self::row('undefeated_pirate', TitleUnlockType::FIGHTS_WON, 500, null, 26),
            self::row('great_explorer', TitleUnlockType::ALL_DUNGEONS_COMPLETED, null, null, 27),
            self::row('fight_veteran_50', TitleUnlockType::FIGHTS_WON, 50, null, 28),
            self::row('fight_veteran_100', TitleUnlockType::FIGHTS_WON, 100, null, 29),
            self::row('epic_collector', TitleUnlockType::EPIC_ITEMS_COLLECTED, 10, null, 30),
            self::row('epic_lord', TitleUnlockType::EPIC_EQUIPMENT_FULL, null, null, 31),
            self::row('legendary_lord', TitleUnlockType::LEGENDARY_EQUIPMENT_FULL, null, null, 32),
            self::row('grand_collector', TitleUnlockType::ITEMS_COLLECTED, 200, null, 33),
            self::row('legend_slayer', TitleUnlockType::LEGENDARY_ITEMS_COLLECTED, 25, null, 34),
            self::row('ocean_ruler', TitleUnlockType::LEVEL_REACHED, 150, null, 35),
            self::row('atlantis_master', TitleUnlockType::DUNGEON_COMPLETED, null, 'palac', 36),
            self::row('golden_corsair', TitleUnlockType::GOLD_BALANCE, 50000, null, 37),
            self::row('expedition_veteran', TitleUnlockType::FIGHTS_WON, 2000, null, 38),
            self::row('titan_slayer', TitleUnlockType::ALL_DUNGEONS_AND_LEVEL, 100, null, 39),
            self::row('relic_lord', TitleUnlockType::ITEMS_COLLECTED, 300, null, 40),
            self::row('great_discoverer', TitleUnlockType::BESTIARY_COMPLETE, 50, null, 41),
            self::row('immortal_captain', TitleUnlockType::LEVEL_REACHED, 200, null, 42),
            self::row('fight_archmaster', TitleUnlockType::FIGHTS_WON, 10000, null, 43),
            self::row('atlantis_emperor', TitleUnlockType::LEVEL_REACHED, 250, null, 44),
            self::row('storm_lord', TitleUnlockType::FIGHTS_WON, 7500, null, 45),
            self::row('leviathan_hunter', TitleUnlockType::LEGENDARY_ITEMS_COLLECTED, 50, null, 46),
            self::row('collection_master', TitleUnlockType::ITEMS_COLLECTED, 400, null, 47),
            self::row('grand_seeker', TitleUnlockType::ITEMS_COLLECTED, 500, null, 48),
            self::row('ocean_slayer', TitleUnlockType::FIGHTS_WON, 15000, null, 49),
            self::row('relic_sovereign', TitleUnlockType::EPIC_ITEMS_COLLECTED, 75, null, 50),
            self::row('unbeaten_corsair', TitleUnlockType::LEVEL_REACHED, 225, null, 51),
            self::row('legend_lord', TitleUnlockType::LEGENDARY_ITEMS_COLLECTED, 75, null, 52),
            self::row('greatest_explorer', TitleUnlockType::ITEMS_COLLECTED, 350, null, 53),
            self::row('eternal_captain', TitleUnlockType::LEVEL_REACHED, 300, null, 54),
        ];

        foreach (LevelRankTitleCatalog::definitions() as $def) {
            $rows[] = self::row(
                $def['code'],
                TitleUnlockType::LEVEL_REACHED,
                $def['level'],
                null,
                $def['sortOrder'],
            );
        }

        return $rows;
    }

    /**
     * @return TitleDef
     */
    private static function row(
        string $code,
        TitleUnlockType $unlockType,
        ?int $unlockValue,
        ?string $unlockDungeonId,
        int $sortOrder,
    ): array {
        return [
            'code' => $code,
            'unlockType' => $unlockType,
            'unlockValue' => $unlockValue,
            'unlockDungeonId' => $unlockDungeonId,
            'sortOrder' => $sortOrder,
        ];
    }
}
