<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204210314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        
        // Add image_size to artiste if it doesn't exist
        $artisteTable = $schema->getTable('artiste');
        if (!$artisteTable->hasColumn('image_size')) {
            $this->addSql('ALTER TABLE artiste ADD image_size INT DEFAULT NULL');
        }
        
        // Add foreign key constraint only if it doesn't exist
        if (!$artisteTable->hasForeignKey('FK_9C07354FA76ED395')) {
            $this->addSql('ALTER TABLE artiste ADD CONSTRAINT FK_9C07354FA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        }
        
        // Add foreign key constraint to notification only if it doesn't exist
        $notificationTable = $schema->getTable('notification');
        if (!$notificationTable->hasForeignKey('FK_BF5476CAA76ED395')) {
            $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        }
        
        // Add image_size to oeuvre if it doesn't exist, and make image_path nullable
        $oeuvreTable = $schema->getTable('oeuvre');
        if (!$oeuvreTable->hasColumn('image_size')) {
            $this->addSql('ALTER TABLE oeuvre ADD image_size INT DEFAULT NULL, CHANGE image_path image_path VARCHAR(255) DEFAULT NULL');
        } else {
            $this->addSql('ALTER TABLE oeuvre CHANGE image_path image_path VARCHAR(255) DEFAULT NULL');
        }
        
        // Drop avatar_url from utilisateur if it exists
        $utilisateurTable = $schema->getTable('utilisateur');
        if ($utilisateurTable->hasColumn('avatar_url')) {
            $this->addSql('ALTER TABLE utilisateur DROP avatar_url');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artiste DROP FOREIGN KEY FK_9C07354FA76ED395');
        $this->addSql('ALTER TABLE artiste DROP image_size');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE oeuvre DROP image_size, CHANGE image_path image_path VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD avatar_url VARCHAR(500) DEFAULT NULL');
    }
}
