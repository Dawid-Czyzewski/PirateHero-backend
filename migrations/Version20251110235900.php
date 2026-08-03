<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251110235900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add max_members column to club table with default value 15.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE club ADD max_members INT NOT NULL DEFAULT 15
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE club SET max_members = 15 WHERE max_members IS NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE club DROP max_members
        SQL);
    }
}
