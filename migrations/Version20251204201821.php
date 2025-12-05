<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204201821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE oeuvre_categories DROP FOREIGN KEY FK_40808C08BCF5E72D');
        $this->addSql('ALTER TABLE oeuvre_categories DROP FOREIGN KEY FK_40808C0888194DE8');
        $this->addSql('DROP TABLE oeuvre_categories');
        $this->addSql('ALTER TABLE oeuvre ADD categories JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE oeuvre_categories (oeuvre_id INT NOT NULL, categorie_id INT NOT NULL, INDEX IDX_40808C0888194DE8 (oeuvre_id), INDEX IDX_40808C08BCF5E72D (categorie_id), PRIMARY KEY(oeuvre_id, categorie_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE oeuvre_categories ADD CONSTRAINT FK_40808C08BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE oeuvre_categories ADD CONSTRAINT FK_40808C0888194DE8 FOREIGN KEY (oeuvre_id) REFERENCES oeuvre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE oeuvre DROP categories');
    }
}
