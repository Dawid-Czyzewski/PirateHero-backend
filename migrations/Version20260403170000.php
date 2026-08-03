<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store duel max HP on users_fight for accurate arena replays.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users_fight ADD attacker_max_hp INT DEFAULT NULL, ADD defender_max_hp INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users_fight DROP attacker_max_hp, DROP defender_max_hp');
    }
}
