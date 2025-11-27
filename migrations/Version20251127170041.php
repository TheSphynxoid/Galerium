<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127170041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enchere ADD oeuvre_id INT NOT NULL');
        $this->addSql('ALTER TABLE enchere ADD CONSTRAINT FK_38D1870F88194DE8 FOREIGN KEY (oeuvre_id) REFERENCES `oeuvre` (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_38D1870F88194DE8 ON enchere (oeuvre_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enchere DROP FOREIGN KEY FK_38D1870F88194DE8');
        $this->addSql('DROP INDEX UNIQ_38D1870F88194DE8 ON enchere');
        $this->addSql('ALTER TABLE enchere DROP oeuvre_id');
    }
}
