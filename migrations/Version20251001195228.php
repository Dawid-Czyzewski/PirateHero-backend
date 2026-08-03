<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251001195228 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE fight_move (id INT AUTO_INCREMENT NOT NULL, fight_id INT NOT NULL, player_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', result VARCHAR(255) NOT NULL, move_number INT NOT NULL, damage INT NOT NULL, attacker_health_after INT NOT NULL, defender_health_after INT NOT NULL, INDEX IDX_494E7F37AC6657E4 (fight_id), INDEX IDX_494E7F3799E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE item_statistics (id INT AUTO_INCREMENT NOT NULL, strong_points INT NOT NULL, agility_points INT NOT NULL, critical_chance_points INT NOT NULL, health_points INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE level (id INT AUTO_INCREMENT NOT NULL, exp_to_next_level INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mission (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, gold_reward INT NOT NULL, exp_reward INT NOT NULL, duration_in_seconds INT NOT NULL, energy_cost INT NOT NULL, INDEX IDX_9067F23CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE refresh_token (id INT AUTO_INCREMENT NOT NULL, refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid DATETIME NOT NULL, UNIQUE INDEX UNIQ_C74F2195C74F2195 (refresh_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE store_slot (id INT AUTO_INCREMENT NOT NULL, item_id INT DEFAULT NULL, store_id INT NOT NULL, slot_number INT NOT NULL, INDEX IDX_4EB78EA9126F525E (item_id), INDEX IDX_4EB78EA9B092A811 (store_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE `user` (id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', level_id INT NOT NULL, current_activity_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT '(DC2Type:json)', password VARCHAR(255) NOT NULL, experience_points INT NOT NULL, username VARCHAR(30) NOT NULL, add_date DATETIME NOT NULL, fame_coins INT NOT NULL, gold INT NOT NULL, activate_token VARCHAR(255) DEFAULT NULL, energy_points INT NOT NULL, training_points INT NOT NULL, free_skill_points_available INT NOT NULL, duel_points INT NOT NULL, fame_points INT NOT NULL, UNIQUE INDEX UNIQ_8D93D6495A93C32A (activate_token), INDEX IDX_8D93D6495FB14BA7 (level_id), UNIQUE INDEX UNIQ_8D93D6493F14CB4F (current_activity_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_actual_activity (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', mission_id INT DEFAULT NULL, work_id INT DEFAULT NULL, training_id INT DEFAULT NULL, start_time DATETIME NOT NULL, UNIQUE INDEX UNIQ_36EE4EF5A76ED395 (user_id), INDEX IDX_36EE4EF5BE6CAE90 (mission_id), INDEX IDX_36EE4EF5BB3453DB (work_id), INDEX IDX_36EE4EF5BEFD98D1 (training_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_base_statistics (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', health_points INT NOT NULL, strong_points INT NOT NULL, agility_points INT NOT NULL, critical_chance_points INT NOT NULL, UNIQUE INDEX UNIQ_63B534A1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_equipment (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', UNIQUE INDEX UNIQ_D3D85867A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_equipment_slot (id INT AUTO_INCREMENT NOT NULL, user_equipment_id INT NOT NULL, wearable_item_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_FD0F2E38D1B395FC (user_equipment_id), UNIQUE INDEX UNIQ_FD0F2E3811821F00 (wearable_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_skill_points_prices (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', health_points_price INT NOT NULL, strong_points_price INT NOT NULL, agility_points_price INT NOT NULL, critical_chance_points_price INT NOT NULL, UNIQUE INDEX UNIQ_C0AA66C1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_storage (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', UNIQUE INDEX UNIQ_C77053EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_storage_slot (id INT AUTO_INCREMENT NOT NULL, item_id INT DEFAULT NULL, storage_id INT NOT NULL, slot_number INT NOT NULL, INDEX IDX_25EF9770126F525E (item_id), INDEX IDX_25EF97705CC5DB90 (storage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_store (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', is_free_refresh_available TINYINT(1) NOT NULL, refresh_cost INT NOT NULL, UNIQUE INDEX UNIQ_1D95A32FA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE users_fight (id INT AUTO_INCREMENT NOT NULL, attacker_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', defender_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', result VARCHAR(255) NOT NULL, score_attacker_score INT NOT NULL, score_defender_score INT NOT NULL, INDEX IDX_C6DDE01F65F8CAE3 (attacker_id), INDEX IDX_C6DDE01F4A3E3B6F (defender_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE wearable_item (id INT AUTO_INCREMENT NOT NULL, statistics_id INT NOT NULL, name VARCHAR(150) NOT NULL, type VARCHAR(255) NOT NULL, rarity VARCHAR(255) NOT NULL, price INT NOT NULL, UNIQUE INDEX UNIQ_5201135A9A2595B2 (statistics_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE work (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, hours_count INT NOT NULL, base_gold INT NOT NULL, INDEX IDX_534E6880A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE training (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, duration_in_seconds INT NOT NULL, training_points_cost INT NOT NULL, skill_points_reward INT NOT NULL, stat_type VARCHAR(255) NOT NULL, INDEX IDX_D5128A8FA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE fight_move ADD CONSTRAINT FK_494E7F37AC6657E4 FOREIGN KEY (fight_id) REFERENCES users_fight (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE fight_move ADD CONSTRAINT FK_494E7F3799E6F5DF FOREIGN KEY (player_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mission ADD CONSTRAINT FK_9067F23CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE store_slot ADD CONSTRAINT FK_4EB78EA9126F525E FOREIGN KEY (item_id) REFERENCES wearable_item (id) ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE store_slot ADD CONSTRAINT FK_4EB78EA9B092A811 FOREIGN KEY (store_id) REFERENCES user_store (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` ADD CONSTRAINT FK_8D93D6495FB14BA7 FOREIGN KEY (level_id) REFERENCES level (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` ADD CONSTRAINT FK_8D93D6493F14CB4F FOREIGN KEY (current_activity_id) REFERENCES user_actual_activity (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_actual_activity ADD CONSTRAINT FK_36EE4EF5A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_actual_activity ADD CONSTRAINT FK_36EE4EF5BE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_actual_activity ADD CONSTRAINT FK_36EE4EF5BB3453DB FOREIGN KEY (work_id) REFERENCES work (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_actual_activity ADD CONSTRAINT FK_36EE4EF5BEFD98D1 FOREIGN KEY (training_id) REFERENCES training (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_base_statistics ADD CONSTRAINT FK_63B534A1A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_equipment ADD CONSTRAINT FK_D3D85867A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_equipment_slot ADD CONSTRAINT FK_FD0F2E38D1B395FC FOREIGN KEY (user_equipment_id) REFERENCES user_equipment (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_equipment_slot ADD CONSTRAINT FK_FD0F2E3811821F00 FOREIGN KEY (wearable_item_id) REFERENCES wearable_item (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_skill_points_prices ADD CONSTRAINT FK_C0AA66C1A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_storage ADD CONSTRAINT FK_C77053EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_storage_slot ADD CONSTRAINT FK_25EF9770126F525E FOREIGN KEY (item_id) REFERENCES wearable_item (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_storage_slot ADD CONSTRAINT FK_25EF97705CC5DB90 FOREIGN KEY (storage_id) REFERENCES user_storage (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_store ADD CONSTRAINT FK_1D95A32FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users_fight ADD CONSTRAINT FK_C6DDE01F65F8CAE3 FOREIGN KEY (attacker_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users_fight ADD CONSTRAINT FK_C6DDE01F4A3E3B6F FOREIGN KEY (defender_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE wearable_item ADD CONSTRAINT FK_5201135A9A2595B2 FOREIGN KEY (statistics_id) REFERENCES item_statistics (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE work ADD CONSTRAINT FK_534E6880A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE training ADD CONSTRAINT FK_D5128A8FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE training DROP FOREIGN KEY FK_D5128A8FA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE fight_move DROP FOREIGN KEY FK_494E7F37AC6657E4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE fight_move DROP FOREIGN KEY FK_494E7F3799E6F5DF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mission DROP FOREIGN KEY FK_9067F23CA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE store_slot DROP FOREIGN KEY FK_4EB78EA9126F525E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE store_slot DROP FOREIGN KEY FK_4EB78EA9B092A811
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6495FB14BA7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6493F14CB4F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_actual_activity DROP FOREIGN KEY FK_36EE4EF5A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_actual_activity DROP FOREIGN KEY FK_36EE4EF5BE6CAE90
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_actual_activity DROP FOREIGN KEY FK_36EE4EF5BB3453DB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_actual_activity DROP FOREIGN KEY FK_36EE4EF5BEFD98D1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_base_statistics DROP FOREIGN KEY FK_63B534A1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_equipment DROP FOREIGN KEY FK_D3D85867A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_equipment_slot DROP FOREIGN KEY FK_FD0F2E38D1B395FC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_equipment_slot DROP FOREIGN KEY FK_FD0F2E3811821F00
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_skill_points_prices DROP FOREIGN KEY FK_C0AA66C1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_storage DROP FOREIGN KEY FK_C77053EA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_storage_slot DROP FOREIGN KEY FK_25EF9770126F525E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_storage_slot DROP FOREIGN KEY FK_25EF97705CC5DB90
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_store DROP FOREIGN KEY FK_1D95A32FA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users_fight DROP FOREIGN KEY FK_C6DDE01F65F8CAE3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users_fight DROP FOREIGN KEY FK_C6DDE01F4A3E3B6F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE wearable_item DROP FOREIGN KEY FK_5201135A9A2595B2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE work DROP FOREIGN KEY FK_534E6880A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE fight_move
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE item_statistics
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE level
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE mission
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE refresh_token
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE store_slot
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE `user`
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_actual_activity
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_base_statistics
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_equipment
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_equipment_slot
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_skill_points_prices
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_storage
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_storage_slot
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_store
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE users_fight
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE wearable_item
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE work
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE training
        SQL);
    }
}
