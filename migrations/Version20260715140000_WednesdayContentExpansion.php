<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715140000_WednesdayContentExpansion extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Wednesday RC: 16 new quests, 10 new titles, collector/fight/level progression';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO quest_template (code, title, description, category, target_value, target_dungeon_id, reward_type, reward_amount, secondary_reward_type, secondary_reward_amount, is_active, `order`) VALUES
            ('collector_200', 'Kolekcjoner VI', 'Zdobądź 200 różnych przedmiotów', 'ITEMS_COLLECTED', 200, NULL, 'GOLD', 1800, 'EXPERIENCE', 2200, 1, 48),
            ('collector_250', 'Kolekcjoner VII', 'Zdobądź 250 różnych przedmiotów', 'ITEMS_COLLECTED', 250, NULL, 'GOLD', 2000, 'EXPERIENCE', 2500, 1, 49),
            ('collector_300', 'Kolekcjoner VIII', 'Zdobądź 300 różnych przedmiotów', 'ITEMS_COLLECTED', 300, NULL, 'GOLD', 2500, 'diamonds', 5, 1, 50),
            ('epic_collector_25', 'Arcymistrz Epickich Reliktów', 'Zdobądź 25 przedmiotów Epickich', 'EPIC_ITEM_COLLECTED', 25, NULL, 'EXPERIENCE', 2500, 'GOLD', 1500, 1, 51),
            ('legendary_collector_20', 'Łowca Legend III', 'Zdobądź 20 przedmiotów Legendarnych', 'LEGENDARY_ITEM_COLLECTED', 20, NULL, 'diamonds', 6, NULL, NULL, 1, 52),
            ('legendary_collector_25', 'Łowca Legend IV', 'Zdobądź 25 przedmiotów Legendarnych', 'LEGENDARY_ITEM_COLLECTED', 25, NULL, 'ITEM', 1, NULL, NULL, 1, 53),
            ('legendary_collector_30', 'Łowca Legend V', 'Zdobądź 30 przedmiotów Legendarnych', 'LEGENDARY_ITEM_COLLECTED', 30, NULL, 'diamonds', 8, NULL, NULL, 1, 54),
            ('level_125', 'Kapitan Legend', 'Osiągnij poziom 125', 'LEVEL_UP', 125, NULL, 'EXPERIENCE', 4000, 'GOLD', 3000, 1, 55),
            ('level_150', 'Władca Fal', 'Osiągnij poziom 150', 'LEVEL_UP', 150, NULL, 'EXPERIENCE', 5000, 'diamonds', 5, 1, 56),
            ('level_175', 'Strażnik Głębin', 'Osiągnij poziom 175', 'LEVEL_UP', 175, NULL, 'EXPERIENCE', 6000, 'GOLD', 4000, 1, 57),
            ('level_200', 'Nieśmiertelny Kapitan', 'Osiągnij poziom 200', 'LEVEL_UP', 200, NULL, 'diamonds', 10, NULL, NULL, 1, 58),
            ('fight_veteran_2000', 'Weteran Walk III', 'Wygraj 2000 walk', 'FIGHTS_WON', 2000, NULL, 'GOLD', 1500, 'EXPERIENCE', 3000, 1, 59),
            ('fight_veteran_5000', 'Weteran Walk IV', 'Wygraj 5000 walk', 'FIGHTS_WON', 5000, NULL, 'ITEM', 1, 'diamonds', 6, 1, 60),
            ('gold_spent_25000', 'Magnat Portów', 'Wydaj 25000 złota', 'GOLD_SPENT', 25000, NULL, 'diamonds', 4, NULL, NULL, 1, 61),
            ('titles_all_unlocked', 'Pan Tytułów', 'Odblokuj wszystkie tytuły', 'ALL_TITLES_UNLOCKED', 42, NULL, 'diamonds', 10, NULL, NULL, 1, 62),
            ('fight_quests_complete', 'Mistrz Areny', 'Ukończ wszystkie questy z kategorii walki', 'QUEST_LINE_COMPLETED', 1, 'FIGHTS_WON', 'EXPERIENCE', 3500, NULL, NULL, 1, 63)");

        $this->addSql("INSERT INTO player_title (code, name_key, description_key, unlock_type, unlock_value, unlock_dungeon_id, sort_order) VALUES
            ('grand_collector', 'titles.grand_collector.name', 'titles.grand_collector.unlockHint', 'ITEMS_COLLECTED', 200, NULL, 33),
            ('legend_slayer', 'titles.legend_slayer.name', 'titles.legend_slayer.unlockHint', 'LEGENDARY_ITEMS_COLLECTED', 25, NULL, 34),
            ('ocean_ruler', 'titles.ocean_ruler.name', 'titles.ocean_ruler.unlockHint', 'LEVEL_REACHED', 150, NULL, 35),
            ('atlantis_master', 'titles.atlantis_master.name', 'titles.atlantis_master.unlockHint', 'DUNGEON_COMPLETED', NULL, 'palac', 36),
            ('golden_corsair', 'titles.golden_corsair.name', 'titles.golden_corsair.unlockHint', 'GOLD_BALANCE', 50000, NULL, 37),
            ('expedition_veteran', 'titles.expedition_veteran.name', 'titles.expedition_veteran.unlockHint', 'FIGHTS_WON', 2000, NULL, 38),
            ('titan_slayer', 'titles.titan_slayer.name', 'titles.titan_slayer.unlockHint', 'ALL_DUNGEONS_AND_LEVEL', 100, NULL, 39),
            ('relic_lord', 'titles.relic_lord.name', 'titles.relic_lord.unlockHint', 'ITEMS_COLLECTED', 300, NULL, 40),
            ('great_discoverer', 'titles.great_discoverer.name', 'titles.great_discoverer.unlockHint', 'BESTIARY_COMPLETE', 50, NULL, 41),
            ('immortal_captain', 'titles.immortal_captain.name', 'titles.immortal_captain.unlockHint', 'LEVEL_REACHED', 200, NULL, 42)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM quest_template WHERE code IN (
            'collector_200', 'collector_250', 'collector_300', 'epic_collector_25',
            'legendary_collector_20', 'legendary_collector_25', 'legendary_collector_30',
            'level_125', 'level_150', 'level_175', 'level_200',
            'fight_veteran_2000', 'fight_veteran_5000', 'gold_spent_25000',
            'titles_all_unlocked', 'fight_quests_complete'
        )");

        $this->addSql("DELETE FROM user_title WHERE player_title_id IN (
            SELECT id FROM player_title WHERE code IN (
                'grand_collector', 'legend_slayer', 'ocean_ruler', 'atlantis_master', 'golden_corsair',
                'expedition_veteran', 'titan_slayer', 'relic_lord', 'great_discoverer', 'immortal_captain'
            )
        )");

        $this->addSql("DELETE FROM player_title WHERE code IN (
            'grand_collector', 'legend_slayer', 'ocean_ruler', 'atlantis_master', 'golden_corsair',
            'expedition_veteran', 'titan_slayer', 'relic_lord', 'great_discoverer', 'immortal_captain'
        )");
    }
}
