<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709140000_ThursdayProgression extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Thursday progression: rare equipment stat, new quests and titles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_statistics ADD rare_equipment_full_reached INT NOT NULL DEFAULT 0');

        $this->addSql("INSERT INTO player_title (code, name_key, description_key, unlock_type, unlock_value, unlock_dungeon_id, sort_order) VALUES
            ('treasure_hunter', 'titles.treasure_hunter.name', 'titles.treasure_hunter.unlockHint', 'ITEMS_COLLECTED', 100, NULL, 10),
            ('dungeon_master', 'titles.dungeon_master.name', 'titles.dungeon_master.unlockHint', 'ALL_DUNGEONS_COMPLETED', NULL, NULL, 11),
            ('veteran_collector', 'titles.veteran_collector.name', 'titles.veteran_collector.unlockHint', 'RARE_EQUIPMENT_FULL', NULL, NULL, 12)");

        $this->addSql("INSERT INTO quest_template (title, description, category, target_value, reward_type, reward_amount, is_active, `order`) VALUES
            ('Kolekcjoner II', 'Zdobądź 25 przedmiotów', 'ITEMS_COLLECTED', 25, 'GOLD', 500, 1, 16),
            ('Pełny zestaw Rare', 'Załóż pełny zestaw Rare', 'RARE_EQUIPMENT_FULL', 1, 'diamonds', 2, 1, 17),
            ('Weteran walk', 'Pokonaj 100 przeciwników', 'FIGHTS_WON', 100, 'ITEM', 1, 1, 18),
            ('Mistrz lochów', 'Ukończ wszystkie lochy', 'ALL_DUNGEONS_COMPLETED', 1, 'EXPERIENCE', 1500, 1, 19)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM quest_template WHERE title IN (
            'Kolekcjoner II',
            'Pełny zestaw Rare',
            'Weteran walk',
            'Mistrz lochów'
        )");
        $this->addSql("DELETE FROM user_title WHERE player_title_id IN (
            SELECT id FROM player_title WHERE code IN ('treasure_hunter', 'dungeon_master', 'veteran_collector')
        )");
        $this->addSql("DELETE FROM player_title WHERE code IN ('treasure_hunter', 'dungeon_master', 'veteran_collector')");
        $this->addSql('ALTER TABLE user_statistics DROP rare_equipment_full_reached');
    }
}
