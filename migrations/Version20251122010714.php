<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251122010714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename mission_template_id to quest_template_id in user_quest table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            SET @constraint_name = (
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'user_quest' 
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                AND CONSTRAINT_NAME LIKE '%62454559%'
                LIMIT 1
            );
        SQL);

        $this->addSql(<<<'SQL'
            SET @sql = IF(@constraint_name IS NOT NULL, 
                CONCAT('ALTER TABLE user_quest DROP FOREIGN KEY ', @constraint_name), 
                'SELECT 1');
        SQL);

        $this->addSql(<<<'SQL'
            PREPARE stmt FROM @sql;
        SQL);

        $this->addSql(<<<'SQL'
            EXECUTE stmt;
        SQL);

        $this->addSql(<<<'SQL'
            DEALLOCATE PREPARE stmt;
        SQL);

        $this->addSql('ALTER TABLE user_quest CHANGE mission_template_id quest_template_id INT NOT NULL');

        $this->addSql('ALTER TABLE user_quest ADD CONSTRAINT FK_A1D5034F62454559 FOREIGN KEY (quest_template_id) REFERENCES quest_template (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE user_quest DROP FOREIGN KEY FK_A1D5034F62454559
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_a1d5034f62454559 ON user_quest
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_quest CHANGE quest_template_id mission_template_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_A1D5034F62454559 ON user_quest (mission_template_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_quest ADD CONSTRAINT FK_A1D5034F62454559 FOREIGN KEY (mission_template_id) REFERENCES quest_template (id)
        SQL);
    }
}
