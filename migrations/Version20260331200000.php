<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331200000 extends AbstractMigration
{
    private const SEED_CODES = [
        'pirate-hat',
        'captain-cutlass',
        'leather-jerkin',
        'kraken-medallion',
        'thief-signet',
        'sailor-boots',
        'admiral-blade',
        'storm-amulet',
        'raider-ring',
    ];

    public function getDescription(): string
    {
        return 'Remove seeded wearable catalog rows inserted for character dev setup.';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;
        $ph = implode(',', array_fill(0, count(self::SEED_CODES), '?'));

        $rows = $conn->fetchAllAssociative(
            "SELECT id, statistics_id FROM wearable_item WHERE public_code IN ($ph)",
            self::SEED_CODES
        );

        if ($rows === []) {
            return;
        }

        $itemIds = array_map(static fn (array $r): int => (int) $r['id'], $rows);
        $statIds = array_values(array_filter(array_map(static fn (array $r): ?int => isset($r['statistics_id']) ? (int) $r['statistics_id'] : null, $rows)));

        $inItems = implode(',', array_fill(0, count($itemIds), '?'));
        $conn->executeStatement(
            "UPDATE user_equipment_slot SET wearable_item_id = NULL WHERE wearable_item_id IN ($inItems)",
            $itemIds
        );
        $conn->executeStatement(
            "UPDATE user_storage_slot SET item_id = NULL WHERE item_id IN ($inItems)",
            $itemIds
        );

        $conn->executeStatement(
            "DELETE FROM wearable_item WHERE id IN ($inItems)",
            $itemIds
        );

        if ($statIds !== []) {
            $inStats = implode(',', array_fill(0, count($statIds), '?'));
            $conn->executeStatement(
                "DELETE FROM item_statistics WHERE id IN ($inStats)",
                $statIds
            );
        }
    }

    public function down(Schema $schema): void
    {
    }
}
