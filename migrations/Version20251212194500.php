<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251212194500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reset_code and reset_code_expires_at to utilisateur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE utilisateur ADD reset_code VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE utilisateur ADD reset_code_expires_at DATETIME DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP reset_code');
        $this->addSql('ALTER TABLE utilisateur DROP reset_code_expires_at');
    }
}
