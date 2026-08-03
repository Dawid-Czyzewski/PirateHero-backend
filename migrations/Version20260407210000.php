<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Nowy katalog sklepowych wspomagaczy (nowe public_code + klucze i18n). Czyści aktywne sesje.
 */
final class Version20260407210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace shop booster catalog with new ids (mis_*, trn_*, wrk_*, skl_*); clear sessions.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM user_shop_booster_session');
        $this->addSql('DELETE FROM shop_booster');

        $h = 96;
        $rows = [
            ['mis_1', 'missions', 'gold', 400, $h, 'shopBooster.catalog.mis_1.name', 'shopBooster.catalog.mis_1.description', '+5%', 1],
            ['mis_2', 'missions', 'gold', 1200, $h, 'shopBooster.catalog.mis_2.name', 'shopBooster.catalog.mis_2.description', '+15%', 2],
            ['mis_3', 'missions', 'premium', 5, $h, 'shopBooster.catalog.mis_3.name', 'shopBooster.catalog.mis_3.description', '+40%', 3],
            ['trn_1', 'training', 'gold', 400, $h, 'shopBooster.catalog.trn_1.name', 'shopBooster.catalog.trn_1.description', '+5 pkt treningu', 4],
            ['trn_2', 'training', 'gold', 1200, $h, 'shopBooster.catalog.trn_2.name', 'shopBooster.catalog.trn_2.description', '+15 pkt treningu', 5],
            ['trn_3', 'training', 'premium', 5, $h, 'shopBooster.catalog.trn_3.name', 'shopBooster.catalog.trn_3.description', '+40 pkt treningu', 6],
            ['wrk_1', 'work', 'gold', 400, $h, 'shopBooster.catalog.wrk_1.name', 'shopBooster.catalog.wrk_1.description', '+5%', 7],
            ['wrk_2', 'work', 'gold', 1200, $h, 'shopBooster.catalog.wrk_2.name', 'shopBooster.catalog.wrk_2.description', '+15%', 8],
            ['wrk_3', 'work', 'premium', 5, $h, 'shopBooster.catalog.wrk_3.name', 'shopBooster.catalog.wrk_3.description', '+40%', 9],
            ['skl_1', 'skills', 'gold', 400, $h, 'shopBooster.catalog.skl_1.name', 'shopBooster.catalog.skl_1.description', '+5%', 10],
            ['skl_2', 'skills', 'gold', 1200, $h, 'shopBooster.catalog.skl_2.name', 'shopBooster.catalog.skl_2.description', '+15%', 11],
            ['skl_3', 'skills', 'premium', 5, $h, 'shopBooster.catalog.skl_3.name', 'shopBooster.catalog.skl_3.description', '+40%', 12],
        ];

        foreach ($rows as $r) {
            $this->addSql(
                'INSERT INTO shop_booster (public_code, category, currency, price, duration_hours, name, description, effect, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                $r,
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
