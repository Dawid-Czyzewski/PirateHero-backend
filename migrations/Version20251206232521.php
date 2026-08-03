<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251206232521 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_system column to club_message table for system messages';
    }

    public function up(Schema $schema): void
    {
        $connection = $this->connection;
        $schemaManager = $connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns('club_message');

        $columnExists = false;
        foreach ($columns as $column) {
            if ($column->getName() === 'is_system') {
                $columnExists = true;
                break;
            }
        }

        if (!$columnExists) {
            $this->addSql(<<<'SQL'
                ALTER TABLE club_message ADD is_system TINYINT(1) DEFAULT 0 NOT NULL
            SQL);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE club_message DROP is_system
        SQL);
    }
}
