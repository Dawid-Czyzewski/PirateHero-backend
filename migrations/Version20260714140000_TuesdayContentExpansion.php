<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714140000_TuesdayContentExpansion extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tuesday RC: quest codes/i18n, 21 new quests, 10 new titles, epic/legendary progression stats';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_statistics ADD epic_items_collected INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user_statistics ADD epic_equipment_full_reached INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user_statistics ADD legendary_equipment_full_reached INT NOT NULL DEFAULT 0');

        $this->addSql('ALTER TABLE quest_template ADD code VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE quest_template ADD target_dungeon_id VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_QUEST_TEMPLATE_CODE ON quest_template (code)');

        $this->backfillQuestCodes();

        $this->addSql("INSERT INTO quest_template (code, title, description, category, target_value, target_dungeon_id, reward_type, reward_amount, secondary_reward_type, secondary_reward_amount, is_active, `order`) VALUES
            ('fight_veteran_250', 'Weteran Areny II', 'Wygraj 250 walk na arenie', 'FIGHTS_WON', 250, NULL, 'GOLD', 800, 'EXPERIENCE', 1200, 1, 27),
            ('dungeon_krypta', 'Ukończ Kryptę', 'Przejdź wszystkie etapy lochu Krypta', 'DUNGEON_COMPLETED', 1, 'krypta', 'EXPERIENCE', 600, NULL, NULL, 1, 28),
            ('dungeon_kraken', 'Ukończ Krakena', 'Pokonaj wszystkie fale lochu Krakena', 'DUNGEON_COMPLETED', 1, 'kraken', 'GOLD', 700, NULL, NULL, 1, 29),
            ('dungeon_forteca', 'Ukończ Fortecę Czarnobrodego', 'Zdobądź twierdzę Czarnobrodego', 'DUNGEON_COMPLETED', 1, 'forteca', 'EXPERIENCE', 900, NULL, NULL, 1, 30),
            ('dungeon_wulkan', 'Ukończ Wulkan Davy''ego Jonesa', 'Przejdź przez wulkaniczne głębiny', 'DUNGEON_COMPLETED', 1, 'wulkan', 'GOLD', 1100, NULL, NULL, 1, 31),
            ('dungeon_palac', 'Ukończ Pałac Posejdona', 'Dotrzyj do tronu w Pałacu Posejdona', 'DUNGEON_COMPLETED', 1, 'palac', 'diamonds', 2, NULL, NULL, 1, 32),
            ('dungeon_all_bosses', 'Pogromca Bossów Lochów', 'Pokonaj wszystkich bossów lochów', 'ALL_DUNGEONS_COMPLETED', 1, NULL, 'ITEM', 1, NULL, NULL, 1, 33),
            ('bestiary_complete', 'Wielki Odkrywca Bestiariusza', 'Odkryj wszystkie wpisy bestiariusza', 'BESTIARY_ENTRIES_DISCOVERED', 50, NULL, 'EXPERIENCE', 2000, NULL, NULL, 1, 34),
            ('dungeon_titles_all', 'Kolekcjoner Tytułów Lochowych', 'Odblokuj wszystkie tytuły za lochy', 'ALL_DUNGEON_TITLES_UNLOCKED', 5, NULL, 'diamonds', 4, NULL, NULL, 1, 35),
            ('collector_100', 'Kolekcjoner V', 'Zdobądź 100 różnych przedmiotów', 'ITEMS_COLLECTED', 100, NULL, 'GOLD', 1200, 'EXPERIENCE', 1800, 1, 36),
            ('collector_150', 'Kolekcjoner Mistrz', 'Zdobądź 150 różnych przedmiotów', 'ITEMS_COLLECTED', 150, NULL, 'GOLD', 1500, 'diamonds', 3, 1, 37),
            ('epic_collector_10', 'Łowca Epickich Skarbów', 'Zdobądź 10 przedmiotów Epickich', 'EPIC_ITEM_COLLECTED', 10, NULL, 'EXPERIENCE', 1500, NULL, NULL, 1, 38),
            ('legendary_collector_15', 'Łowca Legend II', 'Zdobądź 15 przedmiotów Legendarnych', 'LEGENDARY_ITEM_COLLECTED', 15, NULL, 'diamonds', 5, NULL, NULL, 1, 39),
            ('epic_equipment_full', 'Pełny zestaw Epicki', 'Załóż pełny zestaw Epicki', 'EPIC_EQUIPMENT_FULL', 1, NULL, 'EXPERIENCE', 1800, NULL, NULL, 1, 40),
            ('legendary_equipment_full', 'Pełny zestaw Legendarny', 'Załóż pełny zestaw Legendarny', 'LEGENDARY_EQUIPMENT_FULL', 1, NULL, 'diamonds', 4, NULL, NULL, 1, 41),
            ('level_20', 'Kapitan Doświadczony', 'Osiągnij poziom 20', 'LEVEL_UP', 20, NULL, 'GOLD', 800, NULL, NULL, 1, 42),
            ('level_50', 'Elitarny Kapitan', 'Osiągnij poziom 50', 'LEVEL_UP', 50, NULL, 'EXPERIENCE', 2500, 'GOLD', 2000, 1, 43),
            ('level_75', 'Lord Mórz', 'Osiągnij poziom 75', 'LEVEL_UP', 75, NULL, 'EXPERIENCE', 3500, NULL, NULL, 1, 44),
            ('level_100', 'Władca Oceanu', 'Osiągnij poziom 100', 'LEVEL_UP', 100, NULL, 'diamonds', 8, NULL, NULL, 1, 45),
            ('gold_spent_5000', 'Skarbiec Kupca', 'Wydaj 5000 złota', 'GOLD_SPENT', 5000, NULL, 'GOLD', 600, NULL, NULL, 1, 46),
            ('legendary_collector_10', 'Łowca Legend I', 'Zdobądź 10 przedmiotów Legendarnych', 'LEGENDARY_ITEM_COLLECTED', 10, NULL, 'ITEM', 1, NULL, NULL, 1, 47)");

        $this->addSql("INSERT INTO player_title (code, name_key, description_key, unlock_type, unlock_value, unlock_dungeon_id, sort_order) VALUES
            ('undead_slayer', 'titles.undead_slayer.name', 'titles.undead_slayer.unlockHint', 'DUNGEON_COMPLETED', NULL, 'krypta', 18),
            ('beast_hunter', 'titles.beast_hunter.name', 'titles.beast_hunter.unlockHint', 'BESTIARY_COMPLETE', 50, NULL, 19),
            ('black_corsair', 'titles.black_corsair.name', 'titles.black_corsair.unlockHint', 'FIGHTS_WON', 250, NULL, 20),
            ('elite_captain', 'titles.elite_captain.name', 'titles.elite_captain.unlockHint', 'LEVEL_REACHED', 50, NULL, 21),
            ('fortress_lord', 'titles.fortress_lord.name', 'titles.fortress_lord.unlockHint', 'DUNGEON_COMPLETED', NULL, 'forteca', 22),
            ('atlantis_guardian', 'titles.atlantis_guardian.name', 'titles.atlantis_guardian.unlockHint', 'DUNGEON_COMPLETED', NULL, 'palac', 23),
            ('legend_collector', 'titles.legend_collector.name', 'titles.legend_collector.unlockHint', 'LEGENDARY_ITEMS_COLLECTED', 15, NULL, 24),
            ('ocean_master', 'titles.ocean_master.name', 'titles.ocean_master.unlockHint', 'FIGHTS_WON', 1000, NULL, 25),
            ('undefeated_pirate', 'titles.undefeated_pirate.name', 'titles.undefeated_pirate.unlockHint', 'FIGHTS_WON', 500, NULL, 26),
            ('great_explorer', 'titles.great_explorer.name', 'titles.great_explorer.unlockHint', 'ALL_DUNGEONS_COMPLETED', NULL, NULL, 27),
            ('fight_veteran_50', 'titles.fight_veteran_50.name', 'titles.fight_veteran_50.unlockHint', 'FIGHTS_WON', 50, NULL, 28),
            ('fight_veteran_100', 'titles.fight_veteran_100.name', 'titles.fight_veteran_100.unlockHint', 'FIGHTS_WON', 100, NULL, 29),
            ('epic_collector', 'titles.epic_collector.name', 'titles.epic_collector.unlockHint', 'EPIC_ITEMS_COLLECTED', 10, NULL, 30),
            ('epic_lord', 'titles.epic_lord.name', 'titles.epic_lord.unlockHint', 'EPIC_EQUIPMENT_FULL', NULL, NULL, 31),
            ('legendary_lord', 'titles.legendary_lord.name', 'titles.legendary_lord.unlockHint', 'LEGENDARY_EQUIPMENT_FULL', NULL, NULL, 32)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM quest_template WHERE code IN (
            'fight_veteran_250', 'dungeon_krypta', 'dungeon_kraken', 'dungeon_forteca', 'dungeon_wulkan', 'dungeon_palac',
            'dungeon_all_bosses', 'bestiary_complete', 'dungeon_titles_all', 'collector_100', 'collector_150',
            'epic_collector_10', 'legendary_collector_15', 'epic_equipment_full', 'legendary_equipment_full',
            'level_20', 'level_50', 'level_75', 'level_100', 'gold_spent_5000', 'legendary_collector_10'
        )");

        $this->addSql("DELETE FROM user_title WHERE player_title_id IN (
            SELECT id FROM player_title WHERE code IN (
                'undead_slayer', 'beast_hunter', 'black_corsair', 'elite_captain', 'fortress_lord', 'atlantis_guardian',
                'legend_collector', 'ocean_master', 'undefeated_pirate', 'great_explorer', 'fight_veteran_50',
                'fight_veteran_100', 'epic_collector', 'epic_lord', 'legendary_lord'
            )
        )");

        $this->addSql("DELETE FROM player_title WHERE code IN (
            'undead_slayer', 'beast_hunter', 'black_corsair', 'elite_captain', 'fortress_lord', 'atlantis_guardian',
            'legend_collector', 'ocean_master', 'undefeated_pirate', 'great_explorer', 'fight_veteran_50',
            'fight_veteran_100', 'epic_collector', 'epic_lord', 'legendary_lord'
        )");

        $this->addSql('DROP INDEX UNIQ_QUEST_TEMPLATE_CODE ON quest_template');
        $this->addSql('ALTER TABLE quest_template DROP code');
        $this->addSql('ALTER TABLE quest_template DROP target_dungeon_id');
        $this->addSql('ALTER TABLE user_statistics DROP epic_items_collected');
        $this->addSql('ALTER TABLE user_statistics DROP epic_equipment_full_reached');
        $this->addSql('ALTER TABLE user_statistics DROP legendary_equipment_full_reached');
    }

    private function backfillQuestCodes(): void
    {
        $map = [
            'Pierwszy zakup' => 'gold_spent_100',
            'Hojny kupiec' => 'gold_spent_1000',
            'Milioner' => 'gold_spent_10000',
            'Pierwszy poziom' => 'level_2',
            'Doświadczony wojownik' => 'level_5',
            'Mistrz' => 'level_10',
            'Pierwsza wygrana' => 'fight_won_1',
            'Zwycięzca' => 'fight_won_10',
            'Niepokonany' => 'fight_won_50',
            'Pierwsza porażka' => 'fight_lost_1',
            'Nauka przez porażki' => 'fight_lost_10',
            'Wytrwałość' => 'fight_lost_50',
            'Pierwsze 10 Zdobytych Przedmiotów' => 'collector_10',
            'Pierwszy Rare Item' => 'rare_item_1',
            'Pierwszy Pełny Ekwipunek' => 'equipment_full',
            'Kolekcjoner II' => 'collector_25',
            'Pełny zestaw Rare' => 'rare_equipment_full',
            'Weteran walk' => 'fight_veteran_100',
            'Mistrz lochów' => 'all_dungeons',
            'Kolekcjoner III' => 'collector_50',
            'Pogromca Piratów' => 'fight_veteran_500',
            'Legenda Mórz' => 'sea_legend',
            'Kolekcjoner IV' => 'collector_75',
            'Pogromca Potworów' => 'fight_veteran_1000',
            'Zdobywca Legend' => 'legendary_collector_5',
            'Weteran Poziomów' => 'level_35',
        ];

        foreach ($map as $title => $code) {
            $escapedTitle = str_replace("'", "''", $title);
            $this->addSql(sprintf("UPDATE quest_template SET code = '%s' WHERE title = '%s'", $code, $escapedTitle));
        }
    }
}
