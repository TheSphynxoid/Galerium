<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251213010458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire DROP signalements, CHANGE discussion_id discussion_id INT NOT NULL, CHANGE owner_id owner_id INT NOT NULL');
        $this->addSql('ALTER TABLE discussion CHANGE owner_id owner_id INT NOT NULL');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCFB88E14F');
        $this->addSql('DROP INDEX IDX_EF85A2CCFB88E14F ON favori');
        $this->addSql('ALTER TABLE favori DROP utilisateur_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire ADD signalements INT DEFAULT 0 NOT NULL, CHANGE discussion_id discussion_id INT DEFAULT NULL, CHANGE owner_id owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE discussion CHANGE owner_id owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE favori ADD utilisateur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_EF85A2CCFB88E14F ON favori (utilisateur_id)');
    }
}
