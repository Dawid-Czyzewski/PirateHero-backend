<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716140000_ThursdayContentExpansion extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Thursday RC: 20 endgame quests, 12 titles, titles_all_unlocked target 54';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO quest_template (code, title, description, category, target_value, target_dungeon_id, reward_type, reward_amount, secondary_reward_type, secondary_reward_amount, is_active, `order`) VALUES
            ('collector_350', 'Kolekcjoner IX', 'Zdobądź 350 różnych przedmiotów', 'ITEMS_COLLECTED', 350, NULL, 'GOLD', 3000, 'EXPERIENCE', 3500, 1, 64),
            ('collector_400', 'Kolekcjoner X', 'Zdobądź 400 różnych przedmiotów', 'ITEMS_COLLECTED', 400, NULL, 'GOLD', 3500, 'diamonds', 6, 1, 65),
            ('collector_500', 'Kolekcjoner XII', 'Zdobądź 500 różnych przedmiotów', 'ITEMS_COLLECTED', 500, NULL, 'GOLD', 5000, 'diamonds', 10, 1, 66),
            ('epic_collector_50', 'Łowca Epickich III', 'Zdobądź 50 przedmiotów Epickich', 'EPIC_ITEM_COLLECTED', 50, NULL, 'EXPERIENCE', 3000, 'GOLD', 2000, 1, 67),
            ('epic_collector_75', 'Łowca Epickich IV', 'Zdobądź 75 przedmiotów Epickich', 'EPIC_ITEM_COLLECTED', 75, NULL, 'EXPERIENCE', 4000, 'diamonds', 5, 1, 68),
            ('epic_collector_100', 'Łowca Epickich V', 'Zdobądź 100 przedmiotów Epickich', 'EPIC_ITEM_COLLECTED', 100, NULL, 'diamonds', 8, NULL, NULL, 1, 69),
            ('legendary_collector_40', 'Łowca Legend VI', 'Zdobądź 40 przedmiotów Legendarnych', 'LEGENDARY_ITEM_COLLECTED', 40, NULL, 'diamonds', 8, NULL, NULL, 1, 70),
            ('legendary_collector_50', 'Łowca Legend VII', 'Zdobądź 50 przedmiotów Legendarnych', 'LEGENDARY_ITEM_COLLECTED', 50, NULL, 'ITEM', 1, 'diamonds', 6, 1, 71),
            ('legendary_collector_75', 'Łowca Legend VIII', 'Zdobądź 75 przedmiotów Legendarnych', 'LEGENDARY_ITEM_COLLECTED', 75, NULL, 'diamonds', 12, NULL, NULL, 1, 72),
            ('fight_veteran_7500', 'Weteran Walk V', 'Wygraj 7500 walk', 'FIGHTS_WON', 7500, NULL, 'GOLD', 2500, 'EXPERIENCE', 4000, 1, 73),
            ('fight_veteran_10000', 'Weteran Walk VI', 'Wygraj 10000 walk', 'FIGHTS_WON', 10000, NULL, 'ITEM', 1, 'diamonds', 8, 1, 74),
            ('fight_veteran_15000', 'Weteran Walk VII', 'Wygraj 15000 walk', 'FIGHTS_WON', 15000, NULL, 'diamonds', 12, NULL, NULL, 1, 75),
            ('level_225', 'Niepokonany Korsarz', 'Osiągnij poziom 225', 'LEVEL_UP', 225, NULL, 'EXPERIENCE', 7000, 'GOLD', 5000, 1, 76),
            ('level_250', 'Cesarz Atlantydy', 'Osiągnij poziom 250', 'LEVEL_UP', 250, NULL, 'EXPERIENCE', 8000, 'diamonds', 8, 1, 77),
            ('level_300', 'Wieczny Kapitan', 'Osiągnij poziom 300', 'LEVEL_UP', 300, NULL, 'diamonds', 15, NULL, NULL, 1, 78),
            ('gold_spent_50000', 'Król Kupców', 'Wydaj 50000 złota', 'GOLD_SPENT', 50000, NULL, 'diamonds', 6, NULL, NULL, 1, 79),
            ('gold_spent_100000', 'Władca Skarbców', 'Wydaj 100000 złota', 'GOLD_SPENT', 100000, NULL, 'diamonds', 10, NULL, NULL, 1, 80),
            ('collector_quests_complete', 'Mistrz Kolekcji', 'Ukończ wszystkie questy kolekcjonerskie', 'QUEST_LINE_COMPLETED', 1, 'ITEMS_COLLECTED', 'diamonds', 8, NULL, NULL, 1, 81),
            ('level_quests_complete', 'Mistrz Poziomów', 'Ukończ wszystkie questy z serii poziomów', 'QUEST_LINE_COMPLETED', 1, 'LEVEL_UP', 'diamonds', 8, NULL, NULL, 1, 82),
            ('epic_quests_complete', 'Pan Epickich Reliktów', 'Ukończ wszystkie questy epickich przedmiotów', 'QUEST_LINE_COMPLETED', 1, 'EPIC_ITEM_COLLECTED', 'EXPERIENCE', 5000, NULL, NULL, 1, 83)");

        $this->addSql("UPDATE quest_template SET target_value = 54 WHERE code = 'titles_all_unlocked'");

        $this->addSql("INSERT INTO player_title (code, name_key, description_key, unlock_type, unlock_value, unlock_dungeon_id, sort_order) VALUES
            ('fight_archmaster', 'titles.fight_archmaster.name', 'titles.fight_archmaster.unlockHint', 'FIGHTS_WON', 10000, NULL, 43),
            ('atlantis_emperor', 'titles.atlantis_emperor.name', 'titles.atlantis_emperor.unlockHint', 'LEVEL_REACHED', 250, NULL, 44),
            ('storm_lord', 'titles.storm_lord.name', 'titles.storm_lord.unlockHint', 'FIGHTS_WON', 7500, NULL, 45),
            ('leviathan_hunter', 'titles.leviathan_hunter.name', 'titles.leviathan_hunter.unlockHint', 'LEGENDARY_ITEMS_COLLECTED', 50, NULL, 46),
            ('collection_master', 'titles.collection_master.name', 'titles.collection_master.unlockHint', 'ITEMS_COLLECTED', 400, NULL, 47),
            ('grand_seeker', 'titles.grand_seeker.name', 'titles.grand_seeker.unlockHint', 'ITEMS_COLLECTED', 500, NULL, 48),
            ('ocean_slayer', 'titles.ocean_slayer.name', 'titles.ocean_slayer.unlockHint', 'FIGHTS_WON', 15000, NULL, 49),
            ('relic_sovereign', 'titles.relic_sovereign.name', 'titles.relic_sovereign.unlockHint', 'EPIC_ITEMS_COLLECTED', 75, NULL, 50),
            ('unbeaten_corsair', 'titles.unbeaten_corsair.name', 'titles.unbeaten_corsair.unlockHint', 'LEVEL_REACHED', 225, NULL, 51),
            ('legend_lord', 'titles.legend_lord.name', 'titles.legend_lord.unlockHint', 'LEGENDARY_ITEMS_COLLECTED', 75, NULL, 52),
            ('greatest_explorer', 'titles.greatest_explorer.name', 'titles.greatest_explorer.unlockHint', 'ITEMS_COLLECTED', 350, NULL, 53),
            ('eternal_captain', 'titles.eternal_captain.name', 'titles.eternal_captain.unlockHint', 'LEVEL_REACHED', 300, NULL, 54)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM quest_template WHERE code IN (
            'collector_350', 'collector_400', 'collector_500',
            'epic_collector_50', 'epic_collector_75', 'epic_collector_100',
            'legendary_collector_40', 'legendary_collector_50', 'legendary_collector_75',
            'fight_veteran_7500', 'fight_veteran_10000', 'fight_veteran_15000',
            'level_225', 'level_250', 'level_300',
            'gold_spent_50000', 'gold_spent_100000',
            'collector_quests_complete', 'level_quests_complete', 'epic_quests_complete'
        )");

        $this->addSql("UPDATE quest_template SET target_value = 42 WHERE code = 'titles_all_unlocked'");

        $this->addSql("DELETE FROM user_title WHERE player_title_id IN (
            SELECT id FROM player_title WHERE code IN (
                'fight_archmaster', 'atlantis_emperor', 'storm_lord', 'leviathan_hunter',
                'collection_master', 'grand_seeker', 'ocean_slayer', 'relic_sovereign',
                'unbeaten_corsair', 'legend_lord', 'greatest_explorer', 'eternal_captain'
            )
        )");

        $this->addSql("DELETE FROM player_title WHERE code IN (
            'fight_archmaster', 'atlantis_emperor', 'storm_lord', 'leviathan_hunter',
            'collection_master', 'grand_seeker', 'ocean_slayer', 'relic_sovereign',
            'unbeaten_corsair', 'legend_lord', 'greatest_explorer', 'eternal_captain'
        )");
    }
}
