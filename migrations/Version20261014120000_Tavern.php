<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20261014120000_Tavern extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tavern job offers table + UserActualActivity.tavern_job_id FK';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tavern_job (
            id INT AUTO_INCREMENT NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
            title_key VARCHAR(64) NOT NULL,
            duration_minutes INT NOT NULL,
            reward_gold INT NOT NULL,
            reward_diamonds INT NOT NULL DEFAULT 0,
            offered_on DATE NOT NULL,
            started TINYINT(1) NOT NULL DEFAULT 0,
            completed TINYINT(1) NOT NULL DEFAULT 0,
            INDEX IDX_TAVERN_JOB_USER (user_id),
            INDEX IDX_TAVERN_JOB_USER_DAY (user_id, offered_on),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tavern_job ADD CONSTRAINT FK_TAVERN_JOB_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE user_actual_activity ADD tavern_job_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_actual_activity ADD CONSTRAINT FK_UAA_TAVERN_JOB FOREIGN KEY (tavern_job_id) REFERENCES tavern_job (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_UAA_TAVERN_JOB ON user_actual_activity (tavern_job_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_actual_activity DROP FOREIGN KEY FK_UAA_TAVERN_JOB');
        $this->addSql('DROP INDEX IDX_UAA_TAVERN_JOB ON user_actual_activity');
        $this->addSql('ALTER TABLE user_actual_activity DROP tavern_job_id');
        $this->addSql('ALTER TABLE tavern_job DROP FOREIGN KEY FK_TAVERN_JOB_USER');
        $this->addSql('DROP TABLE tavern_job');
    }
}
