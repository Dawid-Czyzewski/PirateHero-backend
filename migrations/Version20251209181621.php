<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251209181621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make current_league and highest_league nullable in user_league table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_league CHANGE current_league current_league INT DEFAULT NULL, CHANGE highest_league highest_league INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_league CHANGE current_league current_league INT NOT NULL, CHANGE highest_league highest_league INT NOT NULL');
    }
}
