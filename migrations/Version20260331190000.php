<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Wearable slot types (helmet..boots), item catalog fields, storage 12 slots.';
    }

    public function up(Schema $schema): void
    {
        $this->connection->executeStatement('ALTER TABLE item_statistics ADD character_stats JSON DEFAULT NULL');
        $this->connection->executeStatement('ALTER TABLE wearable_item ADD public_code VARCHAR(64) DEFAULT NULL');
        $this->connection->executeStatement('CREATE UNIQUE INDEX UNIQ_WEARABLE_PUBLIC_CODE ON wearable_item (public_code)');
        $this->connection->executeStatement('ALTER TABLE wearable_item ADD level INT NOT NULL DEFAULT 1');
        $this->connection->executeStatement('ALTER TABLE wearable_item ADD image_key VARCHAR(64) DEFAULT NULL');

        $this->addSql("UPDATE wearable_item SET type = 'helmet' WHERE type = 'HEAD'");
        $this->addSql("UPDATE wearable_item SET type = 'weapon' WHERE type = 'HANDS'");
        $this->addSql("UPDATE wearable_item SET type = 'armor' WHERE type IN ('SHIRT', 'PANTS')");
        $this->addSql("UPDATE wearable_item SET type = 'boots' WHERE type = 'SHOES'");

        $this->addSql("UPDATE user_equipment_slot SET type = 'helmet' WHERE type = 'HEAD'");
        $this->addSql("UPDATE user_equipment_slot SET type = 'weapon' WHERE type = 'HANDS'");
        $this->addSql("UPDATE user_equipment_slot SET type = 'armor' WHERE type IN ('SHIRT', 'PANTS')");
        $this->addSql("UPDATE user_equipment_slot SET type = 'boots' WHERE type = 'SHOES'");

        $this->addSql("INSERT INTO user_equipment_slot (user_equipment_id, type)
            SELECT ue.id, 'amulet' FROM user_equipment ue
            WHERE NOT EXISTS (
                SELECT 1 FROM user_equipment_slot ues
                WHERE ues.user_equipment_id = ue.id AND ues.type = 'amulet'
            )");
        $this->addSql("INSERT INTO user_equipment_slot (user_equipment_id, type)
            SELECT ue.id, 'ring' FROM user_equipment ue
            WHERE NOT EXISTS (
                SELECT 1 FROM user_equipment_slot ues
                WHERE ues.user_equipment_id = ue.id AND ues.type = 'ring'
            )");

        $this->addSql('INSERT INTO user_storage_slot (storage_id, slot_number, item_id)
            SELECT s.id, n, NULL FROM user_storage s
            CROSS JOIN (
                SELECT 10 AS n UNION SELECT 11 UNION SELECT 12
            ) slots
            WHERE NOT EXISTS (
                SELECT 1 FROM user_storage_slot uss
                WHERE uss.storage_id = s.id AND uss.slot_number = n
            )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_WEARABLE_PUBLIC_CODE ON wearable_item');
        $this->addSql('ALTER TABLE wearable_item DROP public_code, DROP level, DROP image_key');
        $this->addSql('ALTER TABLE item_statistics DROP character_stats');

        $this->addSql("DELETE FROM user_equipment_slot WHERE type IN ('amulet', 'ring')");

        $this->addSql('DELETE FROM user_storage_slot WHERE slot_number IN (10, 11, 12)');

        $this->addSql("UPDATE wearable_item SET type = 'HEAD' WHERE type = 'helmet'");
        $this->addSql("UPDATE wearable_item SET type = 'HANDS' WHERE type = 'weapon'");
        $this->addSql("UPDATE wearable_item SET type = 'SHIRT' WHERE type = 'armor'");
        $this->addSql("UPDATE wearable_item SET type = 'SHOES' WHERE type = 'boots'");

        $this->addSql("UPDATE user_equipment_slot SET type = 'HEAD' WHERE type = 'helmet'");
        $this->addSql("UPDATE user_equipment_slot SET type = 'HANDS' WHERE type = 'weapon'");
        $this->addSql("UPDATE user_equipment_slot SET type = 'SHIRT' WHERE type = 'armor'");
        $this->addSql("UPDATE user_equipment_slot SET type = 'SHOES' WHERE type = 'boots'");
    }
}
