<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Wypełnia pustą tabelę coupon zestawem kuponów testowych / dev.
 * Uruchamia się wyłącznie gdy COUNT(*) = 0 (bezpieczne na istniejących bazach).
 */
final class Version20260408140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed coupon table with dev/test coupons when empty.';
    }

    public function up(Schema $schema): void
    {
        $count = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM coupon');
        if ($count > 0) {
            return;
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $boosterIds = $this->connection->fetchFirstColumn(
            'SELECT id FROM booster_template ORDER BY id ASC LIMIT 3'
        );
        $boosterIds = array_map(static fn (mixed $id): int => (int) $id, $boosterIds);

        $insert = function (
            string $code,
            string $type,
            string $rewardType,
            ?int $rewardValue,
            ?string $rewardDataJson,
        ) use ($now): void {
            $this->connection->executeStatement(
                'INSERT INTO coupon (code, type, reward_type, reward_value, reward_data, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$code, $type, $rewardType, $rewardValue, $rewardDataJson, $now],
            );
        };

        $insert('GOLD100', 'MULTI_USE', 'GOLD', 100, null);
        $insert('GOLD500', 'MULTI_USE', 'GOLD', 500, null);
        $insert('GOLD1000', 'SINGLE_USE', 'GOLD', 1000, null);
        $insert('FAME10', 'MULTI_USE', 'diamonds', 10, null);
        $insert('FAME50', 'MULTI_USE', 'diamonds', 50, null);
        $insert('FAME100', 'SINGLE_USE', 'diamonds', 100, null);

        if (\count($boosterIds) >= 3) {
            $insert('BOOST1', 'MULTI_USE', 'BOOSTER', null, json_encode([
                'boosterTemplateId' => $boosterIds[0],
                'durationDays' => 7,
            ], \JSON_THROW_ON_ERROR));
            $insert('BOOST2', 'MULTI_USE', 'BOOSTER', null, json_encode([
                'boosterTemplateId' => $boosterIds[1],
                'durationDays' => 14,
            ], \JSON_THROW_ON_ERROR));
            $insert('BOOST3', 'SINGLE_USE', 'BOOSTER', null, json_encode([
                'boosterTemplateId' => $boosterIds[2],
                'durationDays' => 30,
            ], \JSON_THROW_ON_ERROR));
        }

        $insert('ITEM1', 'MULTI_USE', 'ITEM', null, json_encode([
            'rarity' => 'RARE',
            'type' => 'weapon',
            'name' => 'Reward Item #1',
        ], \JSON_THROW_ON_ERROR));
        $insert('ITEM2', 'MULTI_USE', 'ITEM', null, json_encode([
            'rarity' => 'EPIC',
            'type' => 'boots',
            'name' => 'Reward Item #2',
        ], \JSON_THROW_ON_ERROR));
        $insert('ITEM3', 'SINGLE_USE', 'ITEM', null, json_encode([
            'rarity' => 'LEGENDARY',
            'type' => 'amulet',
            'name' => 'Legendary Reward Item',
        ], \JSON_THROW_ON_ERROR));

        $insert('TEST_GOLD', 'MULTI_USE', 'GOLD', 50, null);
        $insert('TEST_DIAMONDS', 'MULTI_USE', 'diamonds', 5, null);

        if (\count($boosterIds) >= 1) {
            $insert('TEST_BOOSTER', 'MULTI_USE', 'BOOSTER', null, json_encode([
                'boosterTemplateId' => $boosterIds[0],
                'durationDays' => 3,
            ], \JSON_THROW_ON_ERROR));
        }

        $insert('TEST_ITEM', 'MULTI_USE', 'ITEM', null, json_encode([
            'rarity' => 'UNCOMMON',
            'name' => 'Test Coupon Helmet',
            'type' => 'helmet',
            'stats' => [
                'strongPoints' => 2,
                'agilityPoints' => 2,
                'criticalChancePoints' => 1,
                'healthPoints' => 5,
            ],
        ], \JSON_THROW_ON_ERROR));
    }

    public function down(Schema $schema): void
    {
        // Seed celowo bez automatycznego rollbacku (mogły powstać user_coupon).
    }
}
