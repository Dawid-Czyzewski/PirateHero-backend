<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251207004121 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make author_id nullable in club_message table for system messages';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE club_message CHANGE author_id author_id CHAR(36) DEFAULT NULL COMMENT '(DC2Type:guid)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE club_message CHANGE author_id author_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)'
        SQL);
    }
}
