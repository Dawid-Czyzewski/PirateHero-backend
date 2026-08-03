<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251209180027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique constraint on league_opponent (league_number, position)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX unique_league_position ON league_opponent (league_number, position)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX unique_league_position ON league_opponent');
    }
}
