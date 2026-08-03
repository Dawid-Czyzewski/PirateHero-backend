<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251124174030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE booster_template DROP base_price_gold, DROP base_price_premium
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club ADD requires_invitation TINYINT(1) DEFAULT 1 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quest_template DROP FOREIGN KEY FK_763AA026F8D8AFA6
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_9f9c1e3af8d8afa6 ON quest_template
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_763AA026F8D8AFA6 ON quest_template (reward_item_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quest_template ADD CONSTRAINT FK_763AA026F8D8AFA6 FOREIGN KEY (reward_item_id) REFERENCES wearable_item (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_quest DROP FOREIGN KEY FK_A1D5034F62454559
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_quest DROP FOREIGN KEY FK_A1D5034FA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_c86aec36a76ed395 ON user_quest
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_A1D5034FA76ED395 ON user_quest (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_c86aec3662454559 ON user_quest
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_A1D5034F328A9A4F ON user_quest (quest_template_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_quest ADD CONSTRAINT FK_A1D5034F62454559 FOREIGN KEY (quest_template_id) REFERENCES quest_template (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_quest ADD CONSTRAINT FK_A1D5034FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE booster_template ADD base_price_gold INT NOT NULL, ADD base_price_premium INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club DROP requires_invitation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quest_template DROP FOREIGN KEY FK_763AA026F8D8AFA6
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_763aa026f8d8afa6 ON quest_template
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9F9C1E3AF8D8AFA6 ON quest_template (reward_item_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quest_template ADD CONSTRAINT FK_763AA026F8D8AFA6 FOREIGN KEY (reward_item_id) REFERENCES wearable_item (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_quest DROP FOREIGN KEY FK_A1D5034FA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_quest DROP FOREIGN KEY FK_A1D5034F328A9A4F
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_a1d5034fa76ed395 ON user_quest
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_C86AEC36A76ED395 ON user_quest (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_a1d5034f328a9a4f ON user_quest
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_C86AEC3662454559 ON user_quest (quest_template_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_quest ADD CONSTRAINT FK_A1D5034FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_quest ADD CONSTRAINT FK_A1D5034F328A9A4F FOREIGN KEY (quest_template_id) REFERENCES quest_template (id)
        SQL);
    }
}
