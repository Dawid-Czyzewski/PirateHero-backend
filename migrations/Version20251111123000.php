<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251111123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add gold_contributed and fame_coins_contributed columns to club_member table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE club_member
            ADD gold_contributed INT NOT NULL DEFAULT 0,
            ADD fame_coins_contributed INT NOT NULL DEFAULT 0
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE club_member
            SET gold_contributed = 0, fame_coins_contributed = 0
            WHERE gold_contributed IS NULL OR fame_coins_contributed IS NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE club_member
            DROP gold_contributed,
            DROP fame_coins_contributed
        SQL);
    }
}
