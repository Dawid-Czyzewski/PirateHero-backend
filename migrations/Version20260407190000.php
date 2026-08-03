<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Wspomagacze sklepu: name/description jako klucze i18n; effect skrócony do wartości parsowalnych.
 */
final class Version20260407190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Shop booster catalog: i18n keys for name/description; parser-only effect strings.';
    }

    public function up(Schema $schema): void
    {
        $rows = [
            ['m1', 'shopBooster.catalog.m1.name', 'shopBooster.catalog.m1.description', '+5%'],
            ['m2', 'shopBooster.catalog.m2.name', 'shopBooster.catalog.m2.description', '+15%'],
            ['m3', 'shopBooster.catalog.m3.name', 'shopBooster.catalog.m3.description', '+40%'],
            ['t1', 'shopBooster.catalog.t1.name', 'shopBooster.catalog.t1.description', '+5 pkt treningu'],
            ['t2', 'shopBooster.catalog.t2.name', 'shopBooster.catalog.t2.description', '+15 pkt treningu'],
            ['t3', 'shopBooster.catalog.t3.name', 'shopBooster.catalog.t3.description', '+40 pkt treningu'],
            ['w1', 'shopBooster.catalog.w1.name', 'shopBooster.catalog.w1.description', '+5%'],
            ['w2', 'shopBooster.catalog.w2.name', 'shopBooster.catalog.w2.description', '+15%'],
            ['w3', 'shopBooster.catalog.w3.name', 'shopBooster.catalog.w3.description', '+40%'],
            ['s1', 'shopBooster.catalog.s1.name', 'shopBooster.catalog.s1.description', '+5%'],
            ['s2', 'shopBooster.catalog.s2.name', 'shopBooster.catalog.s2.description', '+15%'],
            ['s3', 'shopBooster.catalog.s3.name', 'shopBooster.catalog.s3.description', '+40%'],
        ];

        foreach ($rows as [$code, $name, $description, $effect]) {
            $this->addSql(
                'UPDATE shop_booster SET name = ?, description = ?, effect = ? WHERE public_code = ?',
                [$name, $description, $effect, $code]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
