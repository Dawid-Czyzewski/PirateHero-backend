<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331203000 extends AbstractMigration
{
    private const TARGET_USER_ID = '901ec659-da8d-4bf0-a217-2cc54fe2cc24';
    private const TEST_SWORD_CODE = 'captain-cutlass-mark-iii';

    public function getDescription(): string
    {
        return 'Add third test sword to target user storage for item comparison.';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;

        $exists = $conn->fetchOne(
            'SELECT id FROM wearable_item WHERE public_code = ? LIMIT 1',
            [self::TEST_SWORD_CODE]
        );
        if ($exists) {
            return;
        }

        $storageId = $conn->fetchOne(
            'SELECT id FROM user_storage WHERE user_id = ? LIMIT 1',
            [self::TARGET_USER_ID]
        );
        if (!$storageId) {
            return;
        }

        $slotNumber = $conn->fetchOne(
            'SELECT slot_number FROM user_storage_slot WHERE storage_id = ? AND item_id IS NULL ORDER BY slot_number ASC LIMIT 1',
            [$storageId]
        );
        if ($slotNumber === false) {
            return;
        }

        $characterStats = json_encode(
            [
                'strength' => 18,
                'agility' => 0,
                'endurance' => 0,
                'intelligence' => 1,
                'luck' => 0,
            ],
            \JSON_THROW_ON_ERROR
        );

        $conn->executeStatement(
            'INSERT INTO item_statistics (strong_points, agility_points, critical_chance_points, health_points, character_stats)
             VALUES (?, ?, ?, ?, ?)',
            [18, 0, 1, 0, $characterStats]
        );
        $statsId = (int) $conn->lastInsertId();

        $conn->executeStatement(
            'INSERT INTO wearable_item (statistics_id, name, type, rarity, price, public_code, level, image_key)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$statsId, 'Captain Cutlass Mk III', 'weapon', 'LEGENDARY', 260, self::TEST_SWORD_CODE, 28, 'sword2']
        );
        $itemId = (int) $conn->lastInsertId();

        $conn->executeStatement(
            'UPDATE user_storage_slot SET item_id = ? WHERE storage_id = ? AND slot_number = ?',
            [$itemId, $storageId, (int) $slotNumber]
        );
    }

    public function down(Schema $schema): void
    {
        $conn = $this->connection;
        $itemId = $conn->fetchOne(
            'SELECT id FROM wearable_item WHERE public_code = ? LIMIT 1',
            [self::TEST_SWORD_CODE]
        );
        if (!$itemId) {
            return;
        }

        $statsId = $conn->fetchOne('SELECT statistics_id FROM wearable_item WHERE id = ? LIMIT 1', [(int) $itemId]);

        $conn->executeStatement('UPDATE user_equipment_slot SET wearable_item_id = NULL WHERE wearable_item_id = ?', [(int) $itemId]);
        $conn->executeStatement('UPDATE user_storage_slot SET item_id = NULL WHERE item_id = ?', [(int) $itemId]);
        $conn->executeStatement('DELETE FROM wearable_item WHERE id = ?', [(int) $itemId]);

        if ($statsId) {
            $conn->executeStatement('DELETE FROM item_statistics WHERE id = ?', [(int) $statsId]);
        }
    }
}
