<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251119210304 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove STORE_VISITS category, delete related mission templates and remove store_visits column from user_statistics';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DELETE um FROM user_mission um
            INNER JOIN mission_template mt ON um.mission_template_id = mt.id
            WHERE mt.category = 'STORE_VISITS'
        SQL);

        $this->addSql(<<<'SQL'
            DELETE FROM mission_template 
            WHERE category = 'STORE_VISITS'
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE user_statistics DROP store_visits
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE user_statistics ADD store_visits INT NOT NULL
        SQL);
    }
}
