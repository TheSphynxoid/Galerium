<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251126221738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE artiste (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, specialite VARCHAR(255) DEFAULT NULL, biographie LONGTEXT DEFAULT NULL, site_web VARCHAR(255) DEFAULT NULL, reseaux_sociaux VARCHAR(255) DEFAULT NULL, photo_profil VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_9C07354FE7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commentaire (id INT AUTO_INCREMENT NOT NULL, discussion_id INT NOT NULL, contenu LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, note INT DEFAULT NULL, statut VARCHAR(255) NOT NULL, INDEX IDX_67F068BC1ADED311 (discussion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE concours (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, statut VARCHAR(50) NOT NULL, regles LONGTEXT DEFAULT NULL, vote_public TINYINT(1) NOT NULL, date_debut_vote DATETIME DEFAULT NULL, date_fin_vote DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE discussion (id INT AUTO_INCREMENT NOT NULL, forum_id INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, INDEX IDX_C0B9F90F29CCBAD0 (forum_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE enchere (id INT AUTO_INCREMENT NOT NULL, prix_de_base DOUBLE PRECISION NOT NULL, prix_actuel DOUBLE PRECISION NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME DEFAULT NULL, statut VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE favori (id INT AUTO_INCREMENT NOT NULL, date_ajout DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE forum (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, date_creation DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, categorie VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `oeuvre` (id INT AUTO_INCREMENT NOT NULL, artiste_id INT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, categorie VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, date_creation DATE DEFAULT NULL, statut VARCHAR(50) NOT NULL, nb_votes INT DEFAULT 0 NOT NULL, nb_commentaires INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_35FE2EFE21D25844 (artiste_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE offre (id INT AUTO_INCREMENT NOT NULL, echere_id INT NOT NULL, montant DOUBLE PRECISION NOT NULL, date_offre DATETIME NOT NULL, INDEX IDX_AF86866F581343CA (echere_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE participation (id INT AUTO_INCREMENT NOT NULL, artiste_id INT NOT NULL, concours_id INT NOT NULL, oeuvre_id INT DEFAULT NULL, votes_public INT DEFAULT 0 NOT NULL, note_jury INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_AB55E24F21D25844 (artiste_id), INDEX IDX_AB55E24FD11E3C7 (concours_id), INDEX IDX_AB55E24F88194DE8 (oeuvre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE test (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(100) NOT NULL, password VARCHAR(30) NOT NULL, nom VARCHAR(30) NOT NULL, prenom VARCHAR(30) NOT NULL, role VARCHAR(30) NOT NULL, date_inscription DATETIME NOT NULL, UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC1ADED311 FOREIGN KEY (discussion_id) REFERENCES discussion (id)');
        $this->addSql('ALTER TABLE discussion ADD CONSTRAINT FK_C0B9F90F29CCBAD0 FOREIGN KEY (forum_id) REFERENCES forum (id)');
        $this->addSql('ALTER TABLE `oeuvre` ADD CONSTRAINT FK_35FE2EFE21D25844 FOREIGN KEY (artiste_id) REFERENCES artiste (id)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866F581343CA FOREIGN KEY (echere_id) REFERENCES enchere (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F21D25844 FOREIGN KEY (artiste_id) REFERENCES artiste (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24FD11E3C7 FOREIGN KEY (concours_id) REFERENCES concours (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F88194DE8 FOREIGN KEY (oeuvre_id) REFERENCES `oeuvre` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC1ADED311');
        $this->addSql('ALTER TABLE discussion DROP FOREIGN KEY FK_C0B9F90F29CCBAD0');
        $this->addSql('ALTER TABLE `oeuvre` DROP FOREIGN KEY FK_35FE2EFE21D25844');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866F581343CA');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F21D25844');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24FD11E3C7');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F88194DE8');
        $this->addSql('DROP TABLE artiste');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE concours');
        $this->addSql('DROP TABLE discussion');
        $this->addSql('DROP TABLE enchere');
        $this->addSql('DROP TABLE favori');
        $this->addSql('DROP TABLE forum');
        $this->addSql('DROP TABLE `oeuvre`');
        $this->addSql('DROP TABLE offre');
        $this->addSql('DROP TABLE participation');
        $this->addSql('DROP TABLE test');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
