<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251213000000_RemoveHighestLeague extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove highest_league column from user_league table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_league DROP COLUMN highest_league');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_league ADD COLUMN highest_league INT DEFAULT NULL');
    }
}
