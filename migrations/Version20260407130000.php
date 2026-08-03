<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sklepowe wspomagacze sesyjne — tabele + katalog zgodny z frontendem (shopBoosterCatalog).
 */
final class Version20260407130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create shop_booster and user_shop_booster_session; seed shop booster catalog.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE shop_booster (
                id INT AUTO_INCREMENT NOT NULL,
                public_code VARCHAR(16) NOT NULL,
                category VARCHAR(255) NOT NULL,
                currency VARCHAR(255) NOT NULL,
                price INT NOT NULL,
                duration_hours INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                description LONGTEXT NOT NULL,
                effect LONGTEXT NOT NULL,
                sort_order INT NOT NULL,
                UNIQUE INDEX uniq_shop_booster_public_code (public_code),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE user_shop_booster_session (
                id INT AUTO_INCREMENT NOT NULL,
                user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)',
                shop_booster_id INT NOT NULL,
                expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX idx_user_shop_booster_session_user_expires (user_id, expires_at),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE user_shop_booster_session
            ADD CONSTRAINT FK_9F2A7B8C_A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE user_shop_booster_session
            ADD CONSTRAINT FK_9F2A7B8C_SHOP_BOOSTER FOREIGN KEY (shop_booster_id) REFERENCES shop_booster (id) ON DELETE CASCADE
        SQL);

        $this->seedCatalogRows();
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_shop_booster_session DROP FOREIGN KEY FK_9F2A7B8C_A76ED395');
        $this->addSql('ALTER TABLE user_shop_booster_session DROP FOREIGN KEY FK_9F2A7B8C_SHOP_BOOSTER');
        $this->addSql('DROP TABLE user_shop_booster_session');
        $this->addSql('DROP TABLE shop_booster');
    }

    private function seedCatalogRows(): void
    {
        $h = 96;
        $rows = [
            ['m1', 'missions', 'gold', 400, $h, 'Mały Kompas', 'Bonus do PD i złota z misji.', '+5% PD i +5% złota z misji', 1],
            ['m2', 'missions', 'gold', 1200, $h, 'Złoty Kompas', 'Większy bonus do PD i złota z misji.', '+15% PD i +15% złota z misji', 2],
            ['m3', 'missions', 'premium', 5, $h, 'Kompas Legend', 'Maksymalny bonus do PD i złota z misji.', '+40% PD i +40% złota z misji', 3],
            ['t1', 'training', 'gold', 400, $h, 'Mały Eliksir Siły', 'Dodatkowe punkty treningu.', '+5 pkt treningu', 4],
            ['t2', 'training', 'gold', 1200, $h, 'Eliksir Mocy', 'Więcej punktów treningu.', '+15 pkt treningu', 5],
            ['t3', 'training', 'premium', 5, $h, 'Eliksir Tytana', 'Największy pakiet punktów treningu.', '+40 pkt treningu', 6],
            ['w1', 'work', 'gold', 400, $h, 'Srebrny Młot', 'Więcej złota z pracy.', '+5% złota z pracy', 7],
            ['w2', 'work', 'gold', 1200, $h, 'Złoty Młot', 'Znacznie więcej złota z pracy.', '+15% złota z pracy', 8],
            ['w3', 'work', 'premium', 5, $h, 'Diamentowy Młot', 'Maksymalny bonus złota z pracy.', '+40% złota z pracy', 9],
            ['s1', 'skills', 'gold', 400, $h, 'Zwój Wiedzy', 'Wzmocnienie atrybutów.', '+5% atrybutów', 10],
            ['s2', 'skills', 'gold', 1200, $h, 'Księga Mądrości', 'Większe wzmocnienie atrybutów.', '+15% atrybutów', 11],
            ['s3', 'skills', 'premium', 5, $h, 'Starożytny Grimuar', 'Maksymalne wzmocnienie atrybutów.', '+40% atrybutów', 12],
        ];

        foreach ($rows as $r) {
            $this->addSql(
                'INSERT INTO shop_booster (public_code, category, currency, price, duration_hours, name, description, effect, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                $r,
            );
        }
    }
}
