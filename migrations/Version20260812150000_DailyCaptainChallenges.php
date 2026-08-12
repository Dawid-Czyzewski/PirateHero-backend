<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812150000_DailyCaptainChallenges extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Daily captain challenges + bonus claim day row';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_daily_challenge (
            id INT AUTO_INCREMENT NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
            challenge_date DATE NOT NULL,
            slot SMALLINT NOT NULL,
            type VARCHAR(32) NOT NULL,
            target_value INT NOT NULL,
            progress INT NOT NULL,
            reward_claimed TINYINT(1) NOT NULL,
            INDEX IDX_DAILY_CHALLENGE_USER (user_id),
            UNIQUE INDEX UNIQ_USER_DAILY_CHALLENGE_SLOT (user_id, challenge_date, slot),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_daily_challenge ADD CONSTRAINT FK_DAILY_CHALLENGE_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE user_daily_challenge_day (
            id INT AUTO_INCREMENT NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
            challenge_date DATE NOT NULL,
            bonus_claimed TINYINT(1) NOT NULL,
            INDEX IDX_DAILY_CHALLENGE_DAY_USER (user_id),
            UNIQUE INDEX UNIQ_USER_DAILY_CHALLENGE_DAY (user_id, challenge_date),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_daily_challenge_day ADD CONSTRAINT FK_DAILY_CHALLENGE_DAY_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_daily_challenge DROP FOREIGN KEY FK_DAILY_CHALLENGE_USER');
        $this->addSql('DROP TABLE user_daily_challenge');
        $this->addSql('ALTER TABLE user_daily_challenge_day DROP FOREIGN KEY FK_DAILY_CHALLENGE_DAY_USER');
        $this->addSql('DROP TABLE user_daily_challenge_day');
    }
}
