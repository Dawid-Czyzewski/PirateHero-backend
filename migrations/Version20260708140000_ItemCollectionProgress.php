<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260708140000_ItemCollectionProgress extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Item collection stats, collector title, and collection quest templates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_statistics ADD items_collected INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user_statistics ADD rare_items_collected INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE user_statistics ADD equipment_full_reached INT NOT NULL DEFAULT 0');

        $this->addSql("INSERT INTO player_title (code, name_key, description_key, unlock_type, unlock_value, unlock_dungeon_id, sort_order) VALUES
            ('collector', 'titles.collector.name', 'titles.collector.unlockHint', 'ITEMS_COLLECTED', 10, NULL, 9)");

        $this->addSql("INSERT INTO quest_template (title, description, category, target_value, reward_type, reward_amount, is_active, `order`) VALUES
            ('Pierwsze 10 Zdobytych Przedmiotów', 'Zdobądź 10 różnych przedmiotów', 'ITEMS_COLLECTED', 10, 'GOLD', 250, 1, 13),
            ('Pierwszy Rare Item', 'Zdobądź pierwszy rzadki przedmiot', 'RARE_ITEM_COLLECTED', 1, 'EXPERIENCE', 300, 1, 14),
            ('Pierwszy Pełny Ekwipunek', 'Załóż przedmioty we wszystkich slotach', 'EQUIPMENT_FULL', 1, 'GOLD', 400, 1, 15)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM quest_template WHERE title IN (
            'Pierwsze 10 Zdobytych Przedmiotów',
            'Pierwszy Rare Item',
            'Pierwszy Pełny Ekwipunek'
        )");
        $this->addSql("DELETE FROM user_title WHERE player_title_id IN (SELECT id FROM player_title WHERE code = 'collector')");
        $this->addSql("DELETE FROM player_title WHERE code = 'collector'");
        $this->addSql('ALTER TABLE user_statistics DROP equipment_full_reached');
        $this->addSql('ALTER TABLE user_statistics DROP rare_items_collected');
        $this->addSql('ALTER TABLE user_statistics DROP items_collected');
    }
}
