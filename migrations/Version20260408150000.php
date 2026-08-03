<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Konfiguracja boostera na kuponie: osobne kolumny zamiast reward_data (JSON).
 * reward_data zostaje wyłącznie pod szablon nagrody ITEM (generowanie przedmiotu przy realizacji;
 * wynik nagrody trafia do user_coupon.reward_received).
 */
final class Version20260408150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Coupon BOOSTER: booster_template_id + booster_duration_days; clear reward_data for BOOSTER after backfill.';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        if (str_contains($platform, 'mysql') || str_contains($platform, 'mariadb')) {
            $this->addSql('ALTER TABLE coupon ADD booster_template_id INT DEFAULT NULL, ADD booster_duration_days INT DEFAULT NULL');
        } else {
            $this->addSql('ALTER TABLE coupon ADD COLUMN booster_template_id INTEGER DEFAULT NULL');
            $this->addSql('ALTER TABLE coupon ADD COLUMN booster_duration_days INTEGER DEFAULT NULL');
        }

        $rows = $this->connection->fetchAllAssociative(
            "SELECT id, reward_data FROM coupon WHERE reward_type = 'BOOSTER' AND reward_data IS NOT NULL"
        );
        foreach ($rows as $row) {
            $raw = $row['reward_data'];
            if (\is_string($raw)) {
                $data = json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);
            } elseif (\is_array($raw)) {
                $data = $raw;
            } else {
                continue;
            }
            $tid = isset($data['boosterTemplateId']) ? (int) $data['boosterTemplateId'] : null;
            $days = isset($data['durationDays']) ? (int) $data['durationDays'] : 7;
            $this->connection->executeStatement(
                'UPDATE coupon SET booster_template_id = ?, booster_duration_days = ?, reward_data = NULL WHERE id = ?',
                [$tid, $days, $row['id']],
            );
        }

        $this->connection->executeStatement("UPDATE coupon SET reward_data = NULL WHERE reward_type = 'BOOSTER'");

        if (str_contains($platform, 'mysql') || str_contains($platform, 'mariadb')) {
            $this->addSql(<<<'SQL'
                ALTER TABLE coupon ADD CONSTRAINT FK_coupon_booster_template 
                FOREIGN KEY (booster_template_id) REFERENCES booster_template (id) ON DELETE SET NULL
            SQL);
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        if (str_contains($platform, 'mysql') || str_contains($platform, 'mariadb')) {
            $this->addSql('ALTER TABLE coupon DROP FOREIGN KEY FK_coupon_booster_template');
        }
        $this->addSql('ALTER TABLE coupon DROP COLUMN booster_template_id');
        $this->addSql('ALTER TABLE coupon DROP COLUMN booster_duration_days');
    }
}
