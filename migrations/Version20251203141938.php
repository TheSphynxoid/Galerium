<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251203141938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, slug VARCHAR(150) NOT NULL, description VARCHAR(255) DEFAULT NULL, color VARCHAR(20) DEFAULT NULL, UNIQUE INDEX UNIQ_497DD6345E237E06 (name), UNIQUE INDEX UNIQ_497DD634989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, type VARCHAR(80) NOT NULL, payload JSON NOT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', read_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oeuvre_categories (oeuvre_id INT NOT NULL, categorie_id INT NOT NULL, INDEX IDX_40808C0888194DE8 (oeuvre_id), INDEX IDX_40808C08BCF5E72D (categorie_id), PRIMARY KEY(oeuvre_id, categorie_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, display_name VARCHAR(120) NOT NULL, avatar_path VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, is_verified TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE oeuvre_categories ADD CONSTRAINT FK_40808C0888194DE8 FOREIGN KEY (oeuvre_id) REFERENCES oeuvre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE oeuvre_categories ADD CONSTRAINT FK_40808C08BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX UNIQ_9C07354FE7927C74 ON artiste');
        $this->addSql('ALTER TABLE artiste ADD user_id INT NOT NULL, ADD slug VARCHAR(160) NOT NULL, ADD specialty VARCHAR(120) DEFAULT NULL, ADD social_links JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD website VARCHAR(255) DEFAULT NULL, ADD avatar_path VARCHAR(255) DEFAULT NULL, ADD is_featured TINYINT(1) DEFAULT 0 NOT NULL, ADD updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP roles, DROP password, DROP nom, DROP prenom, DROP specialite, DROP site_web, DROP reseaux_sociaux, DROP photo_profil, DROP is_active, CHANGE email display_name VARCHAR(180) NOT NULL, CHANGE biographie biography LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE artiste ADD CONSTRAINT FK_9C07354FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C07354F989D9B62 ON artiste (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C07354FA76ED395 ON artiste (user_id)');
        $this->addSql('ALTER TABLE oeuvre ADD title VARCHAR(180) NOT NULL, ADD slug VARCHAR(180) NOT NULL, ADD status VARCHAR(40) NOT NULL, ADD is_commentable TINYINT(1) DEFAULT 1 NOT NULL, ADD updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD views_count INT DEFAULT 0 NOT NULL, ADD votes_count INT DEFAULT 0 NOT NULL, ADD favorites_count INT DEFAULT 0 NOT NULL, DROP categorie, DROP image, DROP date_creation, DROP statut, DROP nb_votes, DROP nb_commentaires, CHANGE description description LONGTEXT NOT NULL, CHANGE titre image_path VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_35FE2EFE989D9B62 ON oeuvre (slug)');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24FD11E3C7');
        $this->addSql('DROP INDEX IDX_AB55E24FD11E3C7 ON participation');
        $this->addSql('ALTER TABLE participation ADD concours_title VARCHAR(180) DEFAULT NULL, ADD status VARCHAR(20) NOT NULL, ADD jury_notes LONGTEXT DEFAULT NULL, ADD score NUMERIC(5, 2) DEFAULT NULL, ADD notified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP concours_id, DROP votes_public, CHANGE oeuvre_id oeuvre_id INT NOT NULL, CHANGE created_at submitted_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE note_jury result_position INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artiste DROP FOREIGN KEY FK_9C07354FA76ED395');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE oeuvre_categories DROP FOREIGN KEY FK_40808C0888194DE8');
        $this->addSql('ALTER TABLE oeuvre_categories DROP FOREIGN KEY FK_40808C08BCF5E72D');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE oeuvre_categories');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP INDEX UNIQ_9C07354F989D9B62 ON artiste');
        $this->addSql('DROP INDEX UNIQ_9C07354FA76ED395 ON artiste');
        $this->addSql('ALTER TABLE artiste ADD roles JSON NOT NULL COMMENT \'(DC2Type:json)\', ADD password VARCHAR(255) NOT NULL, ADD nom VARCHAR(255) NOT NULL, ADD prenom VARCHAR(255) NOT NULL, ADD specialite VARCHAR(255) DEFAULT NULL, ADD site_web VARCHAR(255) DEFAULT NULL, ADD reseaux_sociaux VARCHAR(255) DEFAULT NULL, ADD photo_profil VARCHAR(255) DEFAULT NULL, ADD is_active TINYINT(1) DEFAULT 1 NOT NULL, DROP user_id, DROP slug, DROP specialty, DROP social_links, DROP website, DROP avatar_path, DROP is_featured, DROP updated_at, CHANGE display_name email VARCHAR(180) NOT NULL, CHANGE biography biographie LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C07354FE7927C74 ON artiste (email)');
        $this->addSql('DROP INDEX UNIQ_35FE2EFE989D9B62 ON oeuvre');
        $this->addSql('ALTER TABLE oeuvre ADD categorie VARCHAR(255) DEFAULT NULL, ADD image VARCHAR(255) DEFAULT NULL, ADD date_creation DATE DEFAULT NULL, ADD statut VARCHAR(50) NOT NULL, ADD nb_votes INT DEFAULT 0 NOT NULL, ADD nb_commentaires INT DEFAULT 0 NOT NULL, DROP title, DROP slug, DROP status, DROP is_commentable, DROP updated_at, DROP published_at, DROP views_count, DROP votes_count, DROP favorites_count, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE image_path titre VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE participation ADD concours_id INT NOT NULL, ADD votes_public INT DEFAULT 0 NOT NULL, DROP concours_title, DROP status, DROP jury_notes, DROP score, DROP notified_at, CHANGE oeuvre_id oeuvre_id INT DEFAULT NULL, CHANGE result_position note_jury INT DEFAULT NULL, CHANGE submitted_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24FD11E3C7 FOREIGN KEY (concours_id) REFERENCES concours (id)');
        $this->addSql('CREATE INDEX IDX_AB55E24FD11E3C7 ON participation (concours_id)');
    }
}
