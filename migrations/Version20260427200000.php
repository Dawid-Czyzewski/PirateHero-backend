<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Renames club-related tables to ship* and FK columns (club_id → ship_id, etc.).
 * MySQL/MariaDB only — matches project default DATABASE_URL.
 */
final class Version20260427200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename club tables to ship*, clubs_fight* to ships_fight*; rename FK columns to ship_id / attacker_ship_id / defender_ship_id / ships_fight_id.';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        $this->skipIf(
            !str_contains($platform, 'mysql') && !str_contains($platform, 'mariadb'),
            'Rename club→ship tables: MySQL/MariaDB only.'
        );

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');

        // Drop indexes that reference columns we rename (MySQL keeps old index definitions otherwise).
        $this->dropIndexIfExists('clubs_fight', 'idx_clubs_fight_attacker_created');
        $this->dropIndexIfExists('clubs_fight', 'idx_clubs_fight_defender_created');
        $this->dropIndexIfExists('club_join_request', 'idx_club_join_request_club_pending_unread_created');

        // Single atomic RENAME avoids a transient broken FK name between parent/child renames.
        $this->addSql(<<<'SQL'
RENAME TABLE
    club TO ship,
    club_member TO ship_member,
    club_message TO ship_message,
    club_invitation TO ship_invitation,
    club_join_request TO ship_join_request,
    club_fight_notification TO ship_fight_notification,
    club_removal_notification TO ship_removal_notification,
    clubs_fight TO ships_fight,
    clubs_fight_member TO ships_fight_member,
    clubs_fight_move TO ships_fight_move
SQL);

        // FK columns → ship naming
        $this->addSql('ALTER TABLE ship_member CHANGE club_id ship_id INT NOT NULL');
        $this->addSql('ALTER TABLE ship_message CHANGE club_id ship_id INT NOT NULL');
        $this->addSql('ALTER TABLE ship_invitation CHANGE club_id ship_id INT NOT NULL');
        $this->addSql('ALTER TABLE ship_join_request CHANGE club_id ship_id INT NOT NULL');

        $this->addSql('ALTER TABLE ship_fight_notification CHANGE club_id ship_id INT NOT NULL');
        $this->addSql('ALTER TABLE ship_fight_notification CHANGE attacker_club_id attacker_ship_id INT NOT NULL');
        $this->addSql('ALTER TABLE ship_fight_notification CHANGE defender_club_id defender_ship_id INT NOT NULL');

        $this->addSql('ALTER TABLE ship_removal_notification CHANGE club_id ship_id INT NOT NULL');

        $this->addSql('ALTER TABLE ships_fight CHANGE attacker_club_id attacker_ship_id INT NOT NULL');
        $this->addSql('ALTER TABLE ships_fight CHANGE defender_club_id defender_ship_id INT NOT NULL');

        $this->addSql('ALTER TABLE ships_fight_member CHANGE clubs_fight_id ships_fight_id INT NOT NULL');
        $this->addSql('ALTER TABLE ships_fight_move CHANGE clubs_fight_id ships_fight_id INT NOT NULL');

        // Recreate dropped indexes with new column names
        $this->addSql('CREATE INDEX idx_ship_join_request_ship_pending_unread_created ON ship_join_request (ship_id, approved, is_read, created_at)');
        $this->addSql('CREATE INDEX idx_ships_fight_attacker_created ON ships_fight (attacker_ship_id, created_at)');
        $this->addSql('CREATE INDEX idx_ships_fight_defender_created ON ships_fight (defender_ship_id, created_at)');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        $this->skipIf(
            !str_contains($platform, 'mysql') && !str_contains($platform, 'mariadb'),
            'Rename ship→club tables: MySQL/MariaDB only.'
        );

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');

        $this->dropIndexIfExists('ships_fight', 'idx_ships_fight_defender_created');
        $this->dropIndexIfExists('ships_fight', 'idx_ships_fight_attacker_created');
        $this->dropIndexIfExists('ship_join_request', 'idx_ship_join_request_ship_pending_unread_created');

        $this->addSql('ALTER TABLE ships_fight_move CHANGE ships_fight_id clubs_fight_id INT NOT NULL');
        $this->addSql('ALTER TABLE ships_fight_member CHANGE ships_fight_id clubs_fight_id INT NOT NULL');

        $this->addSql('ALTER TABLE ships_fight CHANGE attacker_ship_id attacker_club_id INT NOT NULL');
        $this->addSql('ALTER TABLE ships_fight CHANGE defender_ship_id defender_club_id INT NOT NULL');

        $this->addSql('ALTER TABLE ship_removal_notification CHANGE ship_id club_id INT NOT NULL');

        $this->addSql('ALTER TABLE ship_fight_notification CHANGE defender_ship_id defender_club_id INT NOT NULL');
        $this->addSql('ALTER TABLE ship_fight_notification CHANGE attacker_ship_id attacker_club_id INT NOT NULL');
        $this->addSql('ALTER TABLE ship_fight_notification CHANGE ship_id club_id INT NOT NULL');

        $this->addSql('ALTER TABLE ship_join_request CHANGE ship_id club_id INT NOT NULL');
        $this->addSql('ALTER TABLE ship_invitation CHANGE ship_id club_id INT NOT NULL');
        $this->addSql('ALTER TABLE ship_message CHANGE ship_id club_id INT NOT NULL');
        $this->addSql('ALTER TABLE ship_member CHANGE ship_id club_id INT NOT NULL');

        $this->addSql(<<<'SQL'
RENAME TABLE
    ship TO club,
    ship_member TO club_member,
    ship_message TO club_message,
    ship_invitation TO club_invitation,
    ship_join_request TO club_join_request,
    ship_fight_notification TO club_fight_notification,
    ship_removal_notification TO club_removal_notification,
    ships_fight TO clubs_fight,
    ships_fight_member TO clubs_fight_member,
    ships_fight_move TO clubs_fight_move
SQL);

        $this->addSql('CREATE INDEX idx_club_join_request_club_pending_unread_created ON club_join_request (club_id, approved, is_read, created_at)');
        $this->addSql('CREATE INDEX idx_clubs_fight_attacker_created ON clubs_fight (attacker_club_id, created_at)');
        $this->addSql('CREATE INDEX idx_clubs_fight_defender_created ON clubs_fight (defender_club_id, created_at)');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $conn = $this->connection;
        $db = $conn->fetchOne('SELECT DATABASE()');
        if ($db === false || $db === null) {
            return;
        }
        $sql = <<<'SQL'
SELECT 1 FROM information_schema.statistics
WHERE table_schema = ? AND table_name = ? AND index_name = ?
SQL;
        $exists = $conn->fetchOne($sql, [$db, $table, $indexName]);
        if ($exists) {
            $this->addSql(sprintf('DROP INDEX %s ON %s', $indexName, $table));
        }
    }
}
