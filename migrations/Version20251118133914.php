<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251118133914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE mission_template (id INT AUTO_INCREMENT NOT NULL, reward_item_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, category VARCHAR(255) NOT NULL, target_value INT NOT NULL, reward_type VARCHAR(255) NOT NULL, reward_amount INT NOT NULL, is_active TINYINT(1) NOT NULL, `order` INT NOT NULL, INDEX IDX_9F9C1E3AF8D8AFA6 (reward_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_mission (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', mission_template_id INT NOT NULL, current_progress INT NOT NULL, is_completed TINYINT(1) NOT NULL, is_reward_claimed TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', completed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_C86AEC36A76ED395 (user_id), INDEX IDX_C86AEC3662454559 (mission_template_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_statistics (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', store_visits INT NOT NULL, gold_spent INT NOT NULL, fights_won INT NOT NULL, trainings_completed INT NOT NULL, works_completed INT NOT NULL, items_bought INT NOT NULL, boosters_used INT NOT NULL, levels_reached INT NOT NULL, UNIQUE INDEX UNIQ_45B44DCEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mission_template ADD CONSTRAINT FK_9F9C1E3AF8D8AFA6 FOREIGN KEY (reward_item_id) REFERENCES wearable_item (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_mission ADD CONSTRAINT FK_C86AEC36A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_mission ADD CONSTRAINT FK_C86AEC3662454559 FOREIGN KEY (mission_template_id) REFERENCES mission_template (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_statistics ADD CONSTRAINT FK_45B44DCEA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club CHANGE skills_upgrade skills_upgrade INT NOT NULL, CHANGE work_upgrade work_upgrade INT NOT NULL, CHANGE missions_upgrade missions_upgrade INT NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE mission_template DROP FOREIGN KEY FK_9F9C1E3AF8D8AFA6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_mission DROP FOREIGN KEY FK_C86AEC36A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_mission DROP FOREIGN KEY FK_C86AEC3662454559
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_statistics DROP FOREIGN KEY FK_45B44DCEA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE mission_template
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_mission
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_statistics
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club CHANGE skills_upgrade skills_upgrade INT DEFAULT 0 NOT NULL, CHANGE work_upgrade work_upgrade INT DEFAULT 0 NOT NULL, CHANGE missions_upgrade missions_upgrade INT DEFAULT 0 NOT NULL
        SQL);
    }
}
