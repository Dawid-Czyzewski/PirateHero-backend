<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Harden club integrity, battle indexes, coupon consistency, and add API idempotency table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX uniq_club_member_user ON club_member (user_id)');
        $this->addSql('ALTER TABLE club ADD CONSTRAINT chk_club_gold_non_negative CHECK (gold >= 0)');
        $this->addSql('ALTER TABLE club ADD CONSTRAINT chk_club_fame_coins_non_negative CHECK (fame_coins >= 0)');
        $this->addSql('ALTER TABLE club ADD CONSTRAINT chk_club_fame_points_non_negative CHECK (fame_points >= 0)');
        $this->addSql('ALTER TABLE club_member ADD CONSTRAINT chk_club_member_gold_contributed_non_negative CHECK (gold_contributed >= 0)');
        $this->addSql('ALTER TABLE club_member ADD CONSTRAINT chk_club_member_fame_coins_contributed_non_negative CHECK (fame_coins_contributed >= 0)');

        $this->addSql('CREATE INDEX idx_club_invitation_user_pending_unread_created ON club_invitation (user_id, accepted, is_read, created_at)');
        $this->addSql('CREATE INDEX idx_club_join_request_club_pending_unread_created ON club_join_request (club_id, approved, is_read, created_at)');
        $this->addSql('CREATE INDEX idx_club_join_request_user_pending_created ON club_join_request (user_id, approved, created_at)');
        $this->addSql('CREATE INDEX idx_club_fight_notification_user_unread_created ON club_fight_notification (user_id, is_read, created_at)');
        $this->addSql('CREATE INDEX idx_club_removal_notification_user_unread_created ON club_removal_notification (user_id, is_read, created_at)');
        $this->addSql('CREATE INDEX idx_league_battle_user_opponent_last_battle ON league_battle (user_id, opponent_id, last_battle_at)');
        $this->addSql('CREATE INDEX idx_league_battle_user_won ON league_battle (user_id, won)');
        $this->addSql('CREATE INDEX idx_users_fight_attacker_created ON users_fight (attacker_id, created_at)');
        $this->addSql('CREATE INDEX idx_users_fight_defender_created ON users_fight (defender_id, created_at)');
        $this->addSql('CREATE INDEX idx_clubs_fight_attacker_created ON clubs_fight (attacker_club_id, created_at)');
        $this->addSql('CREATE INDEX idx_clubs_fight_defender_created ON clubs_fight (defender_club_id, created_at)');
        $this->addSql('CREATE UNIQUE INDEX uniq_clubs_fight_member_fight_user ON clubs_fight_member (clubs_fight_id, user_id)');

        $this->addSql('ALTER TABLE coupon ADD CONSTRAINT chk_coupon_used_fields_consistency CHECK ((used_at IS NULL AND used_by_user_id IS NULL) OR (used_at IS NOT NULL AND used_by_user_id IS NOT NULL))');

        $this->addSql(<<<'SQL'
            CREATE TABLE api_idempotency (
                id INT AUTO_INCREMENT NOT NULL,
                user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)',
                scope VARCHAR(120) NOT NULL,
                idempotency_key VARCHAR(255) NOT NULL,
                request_hash VARCHAR(128) NOT NULL,
                status VARCHAR(30) NOT NULL,
                http_status SMALLINT NOT NULL,
                response_json JSON DEFAULT NULL,
                created_at DATETIME NOT NULL,
                expires_at DATETIME NOT NULL,
                INDEX idx_api_idempotency_expires_at (expires_at),
                UNIQUE INDEX uniq_api_idempotency_user_scope_key (user_id, scope, idempotency_key),
                CONSTRAINT FK_API_IDEMPOTENCY_USER FOREIGN KEY (user_id) REFERENCES `user` (id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE api_idempotency');
        $this->addSql('ALTER TABLE coupon DROP CONSTRAINT chk_coupon_used_fields_consistency');
        $this->addSql('DROP INDEX uniq_clubs_fight_member_fight_user ON clubs_fight_member');
        $this->addSql('DROP INDEX idx_clubs_fight_defender_created ON clubs_fight');
        $this->addSql('DROP INDEX idx_clubs_fight_attacker_created ON clubs_fight');
        $this->addSql('DROP INDEX idx_users_fight_defender_created ON users_fight');
        $this->addSql('DROP INDEX idx_users_fight_attacker_created ON users_fight');
        $this->addSql('DROP INDEX idx_league_battle_user_won ON league_battle');
        $this->addSql('DROP INDEX idx_league_battle_user_opponent_last_battle ON league_battle');
        $this->addSql('DROP INDEX idx_club_removal_notification_user_unread_created ON club_removal_notification');
        $this->addSql('DROP INDEX idx_club_fight_notification_user_unread_created ON club_fight_notification');
        $this->addSql('DROP INDEX idx_club_join_request_user_pending_created ON club_join_request');
        $this->addSql('DROP INDEX idx_club_join_request_club_pending_unread_created ON club_join_request');
        $this->addSql('DROP INDEX idx_club_invitation_user_pending_unread_created ON club_invitation');
        $this->addSql('ALTER TABLE club_member DROP CONSTRAINT chk_club_member_fame_coins_contributed_non_negative');
        $this->addSql('ALTER TABLE club_member DROP CONSTRAINT chk_club_member_gold_contributed_non_negative');
        $this->addSql('ALTER TABLE club DROP CONSTRAINT chk_club_fame_points_non_negative');
        $this->addSql('ALTER TABLE club DROP CONSTRAINT chk_club_fame_coins_non_negative');
        $this->addSql('ALTER TABLE club DROP CONSTRAINT chk_club_gold_non_negative');
        $this->addSql('DROP INDEX uniq_club_member_user ON club_member');
    }
}
