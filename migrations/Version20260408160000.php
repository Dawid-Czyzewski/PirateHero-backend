<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Gwarantuje obecność zestawu kuponów dev/test (te same kody co CouponFixtures / seed pustej tabeli).
 * Idempotentne: wstawia wyłącznie brakujące `code`. BOOSTER wymaga istniejących booster_template.
 */
final class Version20260408160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure dev/test coupon rows exist (insert missing codes only).';
    }

    public function up(Schema $schema): void
    {
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
            ?int $boosterTemplateId,
            ?int $boosterDurationDays,
        ) use ($now): void {
            $exists = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM coupon WHERE code = ?',
                [$code]
            );
            if ($exists > 0) {
                return;
            }

            $this->connection->executeStatement(
                'INSERT INTO coupon (code, type, reward_type, reward_value, reward_data, booster_template_id, booster_duration_days, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $code,
                    $type,
                    $rewardType,
                    $rewardValue,
                    $rewardDataJson,
                    $boosterTemplateId,
                    $boosterDurationDays,
                    $now,
                ],
            );
        };

        $insert('GOLD100', 'MULTI_USE', 'GOLD', 100, null, null, null);
        $insert('GOLD500', 'MULTI_USE', 'GOLD', 500, null, null, null);
        $insert('GOLD1000', 'SINGLE_USE', 'GOLD', 1000, null, null, null);
        $insert('FAME10', 'MULTI_USE', 'diamonds', 10, null, null, null);
        $insert('FAME50', 'MULTI_USE', 'diamonds', 50, null, null, null);
        $insert('FAME100', 'SINGLE_USE', 'diamonds', 100, null, null, null);

        if (\count($boosterIds) >= 3) {
            $insert('BOOST1', 'MULTI_USE', 'BOOSTER', null, null, $boosterIds[0], 7);
            $insert('BOOST2', 'MULTI_USE', 'BOOSTER', null, null, $boosterIds[1], 14);
            $insert('BOOST3', 'SINGLE_USE', 'BOOSTER', null, null, $boosterIds[2], 30);
        }

        $insert('ITEM1', 'MULTI_USE', 'ITEM', null, json_encode([
            'rarity' => 'RARE',
            'type' => 'weapon',
            'name' => 'Reward Item #1',
        ], \JSON_THROW_ON_ERROR), null, null);
        $insert('ITEM2', 'MULTI_USE', 'ITEM', null, json_encode([
            'rarity' => 'EPIC',
            'type' => 'boots',
            'name' => 'Reward Item #2',
        ], \JSON_THROW_ON_ERROR), null, null);
        $insert('ITEM3', 'SINGLE_USE', 'ITEM', null, json_encode([
            'rarity' => 'LEGENDARY',
            'type' => 'amulet',
            'name' => 'Legendary Reward Item',
        ], \JSON_THROW_ON_ERROR), null, null);

        $insert('TEST_GOLD', 'MULTI_USE', 'GOLD', 50, null, null, null);
        $insert('TEST_DIAMONDS', 'MULTI_USE', 'diamonds', 5, null, null, null);

        if (\count($boosterIds) >= 1) {
            $insert('TEST_BOOSTER', 'MULTI_USE', 'BOOSTER', null, null, $boosterIds[0], 3);
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
        ], \JSON_THROW_ON_ERROR), null, null);
    }

    public function down(Schema $schema): void
    {
    }
}
