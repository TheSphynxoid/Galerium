<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204204504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        
        // Only drop participation foreign keys and table if they exist
        if ($schema->hasTable('participation')) {
            $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F88194DE8');
            $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F21D25844');
            $this->addSql('DROP TABLE participation');
        }
        
        // Only drop user table if it exists
        if ($schema->hasTable('user')) {
            $this->addSql('DROP TABLE user');
        }
        
        // Check if foreign key exists on artiste table before dropping
        $artisteTable = $schema->getTable('artiste');
        if ($artisteTable->hasForeignKey('FK_9C07354FA76ED395')) {
            $this->addSql('ALTER TABLE artiste DROP FOREIGN KEY FK_9C07354FA76ED395');
        }
        
        // Add image_size column if it doesn't exist
        if (!$artisteTable->hasColumn('image_size')) {
            $this->addSql('ALTER TABLE artiste ADD image_size INT DEFAULT NULL');
        }
        
        // Add foreign key constraint
        $this->addSql('ALTER TABLE artiste ADD CONSTRAINT FK_9C07354FA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        
        // Check if foreign key exists on notification table before dropping
        $notificationTable = $schema->getTable('notification');
        if ($notificationTable->hasForeignKey('FK_BF5476CAA76ED395')) {
            $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        }
        
        // Add foreign key constraint
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        
        // Add image_size to oeuvre and make image_path nullable
        $oeuvreTable = $schema->getTable('oeuvre');
        if (!$oeuvreTable->hasColumn('image_size')) {
            $this->addSql('ALTER TABLE oeuvre ADD image_size INT DEFAULT NULL, CHANGE image_path image_path VARCHAR(255) DEFAULT NULL');
        } else {
            $this->addSql('ALTER TABLE oeuvre CHANGE image_path image_path VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE participation (id INT AUTO_INCREMENT NOT NULL, artiste_id INT NOT NULL, oeuvre_id INT NOT NULL, result_position INT DEFAULT NULL, submitted_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', concours_title VARCHAR(180) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, status VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, jury_notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, score NUMERIC(5, 2) DEFAULT NULL, notified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_AB55E24F88194DE8 (oeuvre_id), INDEX IDX_AB55E24F21D25844 (artiste_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, display_name VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, avatar_path VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, is_active TINYINT(1) DEFAULT 1 NOT NULL, is_verified TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F88194DE8 FOREIGN KEY (oeuvre_id) REFERENCES oeuvre (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F21D25844 FOREIGN KEY (artiste_id) REFERENCES artiste (id)');
        $this->addSql('ALTER TABLE artiste DROP FOREIGN KEY FK_9C07354FA76ED395');
        $this->addSql('ALTER TABLE artiste DROP image_size');
        $this->addSql('ALTER TABLE artiste ADD CONSTRAINT FK_9C07354FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE oeuvre DROP image_size, CHANGE image_path image_path VARCHAR(255) NOT NULL');
    }
}
