<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260710140000_FridayProgression extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Friday RC: Tier 3 quests, legendary_collector & sea_legend titles, treasure_hunter threshold bump';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quest_template ADD secondary_reward_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE quest_template ADD secondary_reward_amount INT DEFAULT NULL');

        $this->addSql("UPDATE player_title SET unlock_value = 75 WHERE code = 'treasure_hunter'");

        $this->addSql("INSERT INTO player_title (code, name_key, description_key, unlock_type, unlock_value, unlock_dungeon_id, sort_order) VALUES
            ('legendary_collector', 'titles.legendary_collector.name', 'titles.legendary_collector.unlockHint', 'ITEMS_COLLECTED', 100, NULL, 13),
            ('sea_legend', 'titles.sea_legend.name', 'titles.sea_legend.unlockHint', 'ALL_DUNGEONS_AND_LEVEL', 50, NULL, 14)");

        $this->addSql("INSERT INTO quest_template (title, description, category, target_value, reward_type, reward_amount, secondary_reward_type, secondary_reward_amount, is_active, `order`) VALUES
            ('Kolekcjoner III', 'Zdobądź 50 różnych przedmiotów', 'ITEMS_COLLECTED', 50, 'GOLD', 750, 'EXPERIENCE', 1000, 1, 20),
            ('Pogromca Piratów', 'Wygraj 500 walk', 'FIGHTS_WON', 500, 'ITEM', 1, NULL, NULL, 1, 21),
            ('Legenda Mórz', 'Ukończ wszystkie lochy i osiągnij poziom 50', 'ALL_DUNGEONS_AND_LEVEL', 50, 'diamonds', 5, NULL, NULL, 1, 22)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM quest_template WHERE title IN ('Kolekcjoner III', 'Pogromca Piratów', 'Legenda Mórz')");
        $this->addSql("DELETE FROM user_title WHERE player_title_id IN (
            SELECT id FROM player_title WHERE code IN ('legendary_collector', 'sea_legend')
        )");
        $this->addSql("DELETE FROM player_title WHERE code IN ('legendary_collector', 'sea_legend')");
        $this->addSql("UPDATE player_title SET unlock_value = 100 WHERE code = 'treasure_hunter'");
        $this->addSql('ALTER TABLE quest_template DROP secondary_reward_type');
        $this->addSql('ALTER TABLE quest_template DROP secondary_reward_amount');
    }
}
