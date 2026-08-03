<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251212235925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix clubs_fight_member and clubs_fight_move FK/index order (drop FK before indexes)';
    }

    public function up(Schema $schema): void
    {
        $this->migrateClubsFightMember();
        $this->migrateClubsFightMove();
    }

    public function down(Schema $schema): void
    {
        $this->dropForeignKeyIfExists('clubs_fight_member', 'FK_7F0F0B8878CE513C');
        $this->dropForeignKeyIfExists('clubs_fight_member', 'FK_7F0F0B88A76ED395');
        $this->dropIndexIfExists('clubs_fight_member', 'IDX_7F0F0B8878CE513C');
        $this->dropIndexIfExists('clubs_fight_member', 'IDX_7F0F0B88A76ED395');

        $this->addSql('CREATE INDEX IDX_9B1C2D3E4F5A6B7C ON clubs_fight_member (clubs_fight_id)');
        $this->addSql('CREATE INDEX IDX_9B1C2D3E4F5A6B7D ON clubs_fight_member (user_id)');
        $this->addSql('ALTER TABLE clubs_fight_member ADD CONSTRAINT FK_9B1C2D3E4F5A6B7C FOREIGN KEY (clubs_fight_id) REFERENCES clubs_fight (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE clubs_fight_member ADD CONSTRAINT FK_9B1C2D3E4F5A6B7D FOREIGN KEY (user_id) REFERENCES `user` (id)');

        $this->dropForeignKeyIfExists('clubs_fight_move', 'FK_E9CC41BD78CE513C');
        $this->dropForeignKeyIfExists('clubs_fight_move', 'FK_E9CC41BD99E6F5DF');
        $this->dropForeignKeyIfExists('clubs_fight_move', 'FK_E9CC41BD158E0B66');
        $this->dropForeignKeyIfExists('clubs_fight_move', 'FK_A1B2C3D4E5F6A7B8');
        $this->dropForeignKeyIfExists('clubs_fight_move', 'FK_A1B2C3D4E5F6A7B9');
        $this->dropForeignKeyIfExists('clubs_fight_move', 'FK_A1B2C3D4E5F6A7BA');

        $this->dropIndexIfExists('clubs_fight_move', 'IDX_E9CC41BD78CE513C');
        $this->dropIndexIfExists('clubs_fight_move', 'IDX_E9CC41BD99E6F5DF');
        $this->dropIndexIfExists('clubs_fight_move', 'IDX_E9CC41BD158E0B66');

        $this->addSql('CREATE INDEX IDX_A1B2C3D4E5F6A7B8 ON clubs_fight_move (clubs_fight_id)');
        $this->addSql('CREATE INDEX IDX_A1B2C3D4E5F6A7B9 ON clubs_fight_move (player_id)');
        $this->addSql('CREATE INDEX IDX_A1B2C3D4E5F6A7BA ON clubs_fight_move (target_id)');
        $this->addSql('ALTER TABLE clubs_fight_move ADD CONSTRAINT FK_A1B2C3D4E5F6A7B8 FOREIGN KEY (clubs_fight_id) REFERENCES clubs_fight (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE clubs_fight_move ADD CONSTRAINT FK_A1B2C3D4E5F6A7B9 FOREIGN KEY (player_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE clubs_fight_move ADD CONSTRAINT FK_A1B2C3D4E5F6A7BA FOREIGN KEY (target_id) REFERENCES `user` (id)');
    }

    private function migrateClubsFightMember(): void
    {
        $this->dropForeignKeyIfExists('clubs_fight_member', 'FK_9B1C2D3E4F5A6B7C');
        $this->dropForeignKeyIfExists('clubs_fight_member', 'FK_9B1C2D3E4F5A6B7D');
        $this->dropForeignKeyIfExists('clubs_fight_member', 'FK_7F0F0B8878CE513C');
        $this->dropForeignKeyIfExists('clubs_fight_member', 'FK_7F0F0B88A76ED395');

        $this->dropIndexIfExists('clubs_fight_member', 'IDX_9B1C2D3E4F5A6B7C');
        $this->dropIndexIfExists('clubs_fight_member', 'IDX_9B1C2D3E4F5A6B7D');
        $this->dropIndexIfExists('clubs_fight_member', 'IDX_7F0F0B8878CE513C');
        $this->dropIndexIfExists('clubs_fight_member', 'IDX_7F0F0B88A76ED395');

        $this->addSql('CREATE INDEX IDX_7F0F0B8878CE513C ON clubs_fight_member (clubs_fight_id)');
        $this->addSql('CREATE INDEX IDX_7F0F0B88A76ED395 ON clubs_fight_member (user_id)');
        $this->addSql('ALTER TABLE clubs_fight_member ADD CONSTRAINT FK_7F0F0B8878CE513C FOREIGN KEY (clubs_fight_id) REFERENCES clubs_fight (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE clubs_fight_member ADD CONSTRAINT FK_7F0F0B88A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    private function migrateClubsFightMove(): void
    {
        $this->dropForeignKeyIfExists('clubs_fight_move', 'FK_A1B2C3D4E5F6A7B8');
        $this->dropForeignKeyIfExists('clubs_fight_move', 'FK_A1B2C3D4E5F6A7B9');
        $this->dropForeignKeyIfExists('clubs_fight_move', 'FK_A1B2C3D4E5F6A7BA');
        $this->dropForeignKeyIfExists('clubs_fight_move', 'FK_E9CC41BD78CE513C');
        $this->dropForeignKeyIfExists('clubs_fight_move', 'FK_E9CC41BD99E6F5DF');
        $this->dropForeignKeyIfExists('clubs_fight_move', 'FK_E9CC41BD158E0B66');

        $this->dropIndexIfExists('clubs_fight_move', 'IDX_A1B2C3D4E5F6A7B8');
        $this->dropIndexIfExists('clubs_fight_move', 'IDX_A1B2C3D4E5F6A7B9');
        $this->dropIndexIfExists('clubs_fight_move', 'IDX_A1B2C3D4E5F6A7BA');
        $this->dropIndexIfExists('clubs_fight_move', 'IDX_E9CC41BD78CE513C');
        $this->dropIndexIfExists('clubs_fight_move', 'IDX_E9CC41BD99E6F5DF');
        $this->dropIndexIfExists('clubs_fight_move', 'IDX_E9CC41BD158E0B66');

        $this->addSql('CREATE INDEX IDX_E9CC41BD78CE513C ON clubs_fight_move (clubs_fight_id)');
        $this->addSql('CREATE INDEX IDX_E9CC41BD99E6F5DF ON clubs_fight_move (player_id)');
        $this->addSql('CREATE INDEX IDX_E9CC41BD158E0B66 ON clubs_fight_move (target_id)');
        $this->addSql('ALTER TABLE clubs_fight_move ADD CONSTRAINT FK_E9CC41BD78CE513C FOREIGN KEY (clubs_fight_id) REFERENCES clubs_fight (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE clubs_fight_move ADD CONSTRAINT FK_E9CC41BD99E6F5DF FOREIGN KEY (player_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE clubs_fight_move ADD CONSTRAINT FK_E9CC41BD158E0B66 FOREIGN KEY (target_id) REFERENCES `user` (id)');
    }

    private function dropForeignKeyIfExists(string $table, string $constraintName): void
    {
        $db = $this->connection->fetchOne('SELECT DATABASE()');
        if (!$db) {
            return;
        }
        $exists = (int) $this->connection->fetchOne(
            <<<'SQL'
            SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = ?
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            SQL,
            [$db, $table, $constraintName]
        );
        if ($exists > 0) {
            $this->addSql(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $constraintName));
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $db = $this->connection->fetchOne('SELECT DATABASE()');
        if (!$db) {
            return;
        }
        $exists = (int) $this->connection->fetchOne(
            <<<'SQL'
            SELECT COUNT(*) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?
            SQL,
            [$db, $table, $indexName]
        );
        if ($exists > 0) {
            $this->addSql(sprintf('DROP INDEX `%s` ON `%s`', $indexName, $table));
        }
    }
}
