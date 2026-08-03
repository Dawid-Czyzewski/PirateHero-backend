<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Progression\LevelRankTitleCatalog;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20260727190000_LevelRankTitlesEvery5 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add free LEVEL_REACHED rank titles every 5 levels (5–100); bump titles_all_unlocked to 74';
    }

    public function up(Schema $schema): void
    {
        $rows = [];
        foreach (LevelRankTitleCatalog::definitions() as $def) {
            $rows[] = sprintf(
                "('%s', '%s', '%s', 'LEVEL_REACHED', %d, NULL, %d)",
                $def['code'],
                $def['nameKey'],
                $def['descriptionKey'],
                $def['level'],
                $def['sortOrder'],
            );
        }

        $this->addSql(
            'INSERT INTO player_title (code, name_key, description_key, unlock_type, unlock_value, unlock_dungeon_id, sort_order) VALUES '
            .implode(",\n            ", $rows)
        );

        $newTotal = 54 + LevelRankTitleCatalog::count();
        $this->addSql("UPDATE quest_template SET target_value = {$newTotal} WHERE code = 'titles_all_unlocked'");

        foreach (LevelRankTitleCatalog::definitions() as $def) {
            $this->addSql(
                "INSERT INTO user_title (user_id, player_title_id, unlocked_at)
                SELECT u.id, pt.id, NOW()
                FROM `user` u
                INNER JOIN level l ON u.level_id = l.id
                INNER JOIN player_title pt ON pt.code = '{$def['code']}'
                WHERE CAST(l.name AS UNSIGNED) >= {$def['level']}
                AND u.activate_token IS NULL
                AND NOT EXISTS (
                    SELECT 1 FROM user_title ut
                    WHERE ut.user_id = u.id AND ut.player_title_id = pt.id
                )"
            );
        }
    }

    public function down(Schema $schema): void
    {
        $codes = array_map(
            static fn (array $d): string => "'".$d['code']."'",
            LevelRankTitleCatalog::definitions()
        );
        $inList = implode(', ', $codes);

        $this->addSql("DELETE FROM user_title WHERE player_title_id IN (
            SELECT id FROM player_title WHERE code IN ({$inList})
        )");
        $this->addSql("DELETE FROM player_title WHERE code IN ({$inList})");
        $this->addSql("UPDATE quest_template SET target_value = 54 WHERE code = 'titles_all_unlocked'");
    }
}
