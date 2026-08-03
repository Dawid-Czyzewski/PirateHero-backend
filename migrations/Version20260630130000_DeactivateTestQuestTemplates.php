<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Service\Progression\QuestTemplateDefaults;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260630130000_DeactivateTestQuestTemplates extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Deactivate developer/test quest templates so new players no longer receive them';
    }

    public function up(Schema $schema): void
    {
        foreach (QuestTemplateDefaults::TEST_DEV_TEMPLATE_TITLES as $title) {
            $this->addSql(
                'UPDATE quest_template SET is_active = 0 WHERE title = '.$this->connection->quote($title)
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (QuestTemplateDefaults::TEST_DEV_TEMPLATE_TITLES as $title) {
            $this->addSql(
                'UPDATE quest_template SET is_active = 1 WHERE title = '.$this->connection->quote($title)
            );
        }
    }
}
