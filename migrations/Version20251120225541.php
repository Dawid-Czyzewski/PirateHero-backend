<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251120225541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename mission_template to quest_template and user_mission to user_quest';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE mission_template TO quest_template');

        $this->addSql('RENAME TABLE user_mission TO user_quest');

        $this->addSql('ALTER TABLE user_quest DROP FOREIGN KEY FK_C86AEC3662454559');
        $this->addSql('ALTER TABLE user_quest ADD CONSTRAINT FK_A1D5034F62454559 FOREIGN KEY (mission_template_id) REFERENCES quest_template (id)');

        $this->addSql('ALTER TABLE user_quest DROP FOREIGN KEY FK_C86AEC36A76ED395');
        $this->addSql('ALTER TABLE user_quest ADD CONSTRAINT FK_A1D5034FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');

        $this->addSql('ALTER TABLE quest_template DROP FOREIGN KEY FK_9F9C1E3AF8D8AFA6');
        $this->addSql('ALTER TABLE quest_template ADD CONSTRAINT FK_763AA026F8D8AFA6 FOREIGN KEY (reward_item_id) REFERENCES wearable_item (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quest_template DROP FOREIGN KEY FK_763AA026F8D8AFA6');
        $this->addSql('ALTER TABLE quest_template ADD CONSTRAINT FK_9F9C1E3AF8D8AFA6 FOREIGN KEY (reward_item_id) REFERENCES wearable_item (id)');

        $this->addSql('ALTER TABLE user_quest DROP FOREIGN KEY FK_A1D5034FA76ED395');
        $this->addSql('ALTER TABLE user_quest ADD CONSTRAINT FK_C86AEC36A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');

        $this->addSql('ALTER TABLE user_quest DROP FOREIGN KEY FK_A1D5034F62454559');
        $this->addSql('ALTER TABLE user_quest ADD CONSTRAINT FK_C86AEC3662454559 FOREIGN KEY (mission_template_id) REFERENCES quest_template (id)');

        $this->addSql('RENAME TABLE quest_template TO mission_template');

        $this->addSql('RENAME TABLE user_quest TO user_mission');

        $this->addSql('ALTER TABLE user_mission DROP FOREIGN KEY FK_C86AEC3662454559');
        $this->addSql('ALTER TABLE user_mission ADD CONSTRAINT FK_C86AEC3662454559 FOREIGN KEY (mission_template_id) REFERENCES mission_template (id)');
    }
}
