<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251119184230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update user_statistics: add fights_lost, remove items_bought and boosters_used. Remove old mission templates with invalid categories.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE user_statistics ADD fights_lost INT NOT NULL, DROP items_bought, DROP boosters_used
        SQL);

        $this->addSql(<<<'SQL'
            DELETE FROM mission_template 
            WHERE category IN ('TRAININGS_COMPLETED', 'WORKS_COMPLETED', 'ITEMS_BOUGHT', 'BOOSTERS_USED')
        SQL);

        $this->addSql(<<<'SQL'
            DELETE um FROM user_mission um
            LEFT JOIN mission_template mt ON um.mission_template_id = mt.id
            WHERE mt.id IS NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE user_statistics ADD boosters_used INT NOT NULL, CHANGE fights_lost items_bought INT NOT NULL
        SQL);
    }
}
