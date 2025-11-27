<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127165557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire ADD owner_id INT NOT NULL, ADD oeuvre_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC7E3C61F9 FOREIGN KEY (owner_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC88194DE8 FOREIGN KEY (oeuvre_id) REFERENCES `oeuvre` (id)');
        $this->addSql('CREATE INDEX IDX_67F068BC7E3C61F9 ON commentaire (owner_id)');
        $this->addSql('CREATE INDEX IDX_67F068BC88194DE8 ON commentaire (oeuvre_id)');
        $this->addSql('ALTER TABLE discussion ADD owner_id INT NOT NULL');
        $this->addSql('ALTER TABLE discussion ADD CONSTRAINT FK_C0B9F90F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_C0B9F90F7E3C61F9 ON discussion (owner_id)');
        $this->addSql('ALTER TABLE favori ADD oeuvre_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CC88194DE8 FOREIGN KEY (oeuvre_id) REFERENCES `oeuvre` (id)');
        $this->addSql('CREATE INDEX IDX_EF85A2CC88194DE8 ON favori (oeuvre_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC7E3C61F9');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC88194DE8');
        $this->addSql('DROP INDEX IDX_67F068BC7E3C61F9 ON commentaire');
        $this->addSql('DROP INDEX IDX_67F068BC88194DE8 ON commentaire');
        $this->addSql('ALTER TABLE commentaire DROP owner_id, DROP oeuvre_id');
        $this->addSql('ALTER TABLE discussion DROP FOREIGN KEY FK_C0B9F90F7E3C61F9');
        $this->addSql('DROP INDEX IDX_C0B9F90F7E3C61F9 ON discussion');
        $this->addSql('ALTER TABLE discussion DROP owner_id');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CC88194DE8');
        $this->addSql('DROP INDEX IDX_EF85A2CC88194DE8 ON favori');
        $this->addSql('ALTER TABLE favori DROP oeuvre_id');
    }
}
