<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207193132 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vote_concours DROP FOREIGN KEY FK_B1F1F8BFD11E3C7');
        $this->addSql('ALTER TABLE vote_concours DROP FOREIGN KEY FK_B1F1F8BFFB88E14F');
        $this->addSql('ALTER TABLE vote_concours DROP FOREIGN KEY FK_B1F1F8BF6ACE3B73');
        $this->addSql('ALTER TABLE vote_visiteur DROP FOREIGN KEY FK_VOTE_VISITEUR_PARTICIPATION');
        $this->addSql('DROP TABLE vote_concours');
        $this->addSql('DROP TABLE vote_visiteur');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE vote_concours (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, concours_id INT NOT NULL, participation_id INT NOT NULL, date_vote DATETIME NOT NULL, INDEX IDX_B1F1F8BFD11E3C7 (concours_id), UNIQUE INDEX unique_user_concours (utilisateur_id, concours_id), INDEX IDX_B1F1F8BF6ACE3B73 (participation_id), INDEX IDX_B1F1F8BFFB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE vote_visiteur (id INT AUTO_INCREMENT NOT NULL, participation_id INT NOT NULL, visitor_identifier VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, date_vote DATETIME NOT NULL, ip_address VARCHAR(45) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX unique_visitor_participation (visitor_identifier, participation_id), INDEX IDX_VOTE_PARTICIPATION (participation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE vote_concours ADD CONSTRAINT FK_B1F1F8BFD11E3C7 FOREIGN KEY (concours_id) REFERENCES concours (id)');
        $this->addSql('ALTER TABLE vote_concours ADD CONSTRAINT FK_B1F1F8BFFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE vote_concours ADD CONSTRAINT FK_B1F1F8BF6ACE3B73 FOREIGN KEY (participation_id) REFERENCES participation (id)');
        $this->addSql('ALTER TABLE vote_visiteur ADD CONSTRAINT FK_VOTE_VISITEUR_PARTICIPATION FOREIGN KEY (participation_id) REFERENCES participation (id) ON DELETE CASCADE');
    }
}
