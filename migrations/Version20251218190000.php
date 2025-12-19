<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251218190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add business fields to enchere: reservePrice, minIncrement, buyNowPrice, anti-sniping config';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE enchere ADD reserve_price DOUBLE PRECISION DEFAULT NULL, ADD min_increment DOUBLE PRECISION DEFAULT NULL, ADD buy_now_price DOUBLE PRECISION DEFAULT NULL, ADD anti_sniping_threshold_minutes INT DEFAULT 2 NOT NULL, ADD anti_sniping_extension_minutes INT DEFAULT 2 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE enchere DROP reserve_price, DROP min_increment, DROP buy_now_price, DROP anti_sniping_threshold_minutes, DROP anti_sniping_extension_minutes');
    }
}
