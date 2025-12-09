<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209223350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE vote (id INT AUTO_INCREMENT NOT NULL, visiteur_id INT NOT NULL, participation_id INT NOT NULL, concours_id INT NOT NULL, date_vote DATETIME NOT NULL, INDEX IDX_5A1085647F72333D (visiteur_id), INDEX IDX_5A1085646ACE3B73 (participation_id), INDEX IDX_5A108564D11E3C7 (concours_id), UNIQUE INDEX unique_visiteur_concours (visiteur_id, concours_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A1085647F72333D FOREIGN KEY (visiteur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A1085646ACE3B73 FOREIGN KEY (participation_id) REFERENCES participation (id)');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A108564D11E3C7 FOREIGN KEY (concours_id) REFERENCES concours (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A1085647F72333D');
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A1085646ACE3B73');
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A108564D11E3C7');
        $this->addSql('DROP TABLE vote');
    }
}
