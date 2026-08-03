<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713140000_MondayTier4Progression extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Monday RC: Tier 4 quests, master_collector/legendary_hunter/veteran_captain titles, legendaryItemsCollected stat';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_statistics ADD legendary_items_collected INT NOT NULL DEFAULT 0');

        $this->addSql("INSERT INTO player_title (code, name_key, description_key, unlock_type, unlock_value, unlock_dungeon_id, sort_order) VALUES
            ('master_collector', 'titles.master_collector.name', 'titles.master_collector.unlockHint', 'ITEMS_COLLECTED', 85, NULL, 15),
            ('legendary_hunter', 'titles.legendary_hunter.name', 'titles.legendary_hunter.unlockHint', 'LEGENDARY_ITEMS_COLLECTED', 5, NULL, 16),
            ('veteran_captain', 'titles.veteran_captain.name', 'titles.veteran_captain.unlockHint', 'LEVEL_REACHED', 35, NULL, 17)");

        $this->addSql("INSERT INTO quest_template (title, description, category, target_value, reward_type, reward_amount, secondary_reward_type, secondary_reward_amount, is_active, `order`) VALUES
            ('Kolekcjoner IV', 'Zdobądź 75 różnych przedmiotów', 'ITEMS_COLLECTED', 75, 'GOLD', 1000, 'EXPERIENCE', 1500, 1, 23),
            ('Pogromca Potworów', 'Wygraj 1000 walk', 'FIGHTS_WON', 1000, 'ITEM', 1, NULL, NULL, 1, 24),
            ('Zdobywca Legend', 'Zdobądź 5 przedmiotów Legendarnych', 'LEGENDARY_ITEM_COLLECTED', 5, 'diamonds', 3, NULL, NULL, 1, 25),
            ('Weteran Poziomów', 'Osiągnij poziom 35', 'LEVEL_UP', 35, 'EXPERIENCE', 2000, 'GOLD', 1500, 1, 26)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM quest_template WHERE title IN ('Kolekcjoner IV', 'Pogromca Potworów', 'Zdobywca Legend', 'Weteran Poziomów')");
        $this->addSql("DELETE FROM user_title WHERE player_title_id IN (
            SELECT id FROM player_title WHERE code IN ('master_collector', 'legendary_hunter', 'veteran_captain')
        )");
        $this->addSql("DELETE FROM player_title WHERE code IN ('master_collector', 'legendary_hunter', 'veteran_captain')");
        $this->addSql('ALTER TABLE user_statistics DROP legendary_items_collected');
    }
}
