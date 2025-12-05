<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205124946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    /**
     * Vérifie si une clé étrangère existe
     */
    private function foreignKeyExists(string $tableName, string $constraintName): bool
    {
        $result = $this->connection->executeQuery(
            "SELECT COUNT(*) FROM information_schema.table_constraints 
             WHERE constraint_schema = DATABASE() 
             AND table_name = ? 
             AND constraint_name = ? 
             AND constraint_type = 'FOREIGN KEY'",
            [$tableName, $constraintName]
        )->fetchOne();
        
        return (int)$result > 0;
    }

    /**
     * Vérifie si un index existe
     */
    private function indexExists(string $tableName, string $indexName): bool
    {
        $result = $this->connection->executeQuery(
            "SELECT COUNT(*) FROM information_schema.statistics 
             WHERE table_schema = DATABASE() 
             AND table_name = ? 
             AND index_name = ?",
            [$tableName, $indexName]
        )->fetchOne();
        
        return (int)$result > 0;
    }

    /**
     * Vérifie si une colonne existe
     */
    private function columnExists(string $tableName, string $columnName): bool
    {
        $result = $this->connection->executeQuery(
            "SELECT COUNT(*) FROM information_schema.columns 
             WHERE table_schema = DATABASE() 
             AND table_name = ? 
             AND column_name = ?",
            [$tableName, $columnName]
        )->fetchOne();
        
        return (int)$result > 0;
    }

    public function up(Schema $schema): void
    {
        // Supprimer les clés étrangères si elles existent
        if ($this->foreignKeyExists('artiste', 'FK_9C07354FA76ED395')) {
            $this->addSql('ALTER TABLE artiste DROP FOREIGN KEY FK_9C07354FA76ED395');
        }
        
        if ($this->foreignKeyExists('notification', 'FK_BF5476CAA76ED395')) {
            $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        }
        
        // Vérifier si la table existe avant de la créer
        $tableExists = $this->connection->executeQuery(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'oeuvre_categories'"
        )->fetchOne();
        
        if (!$tableExists) {
            $this->addSql('CREATE TABLE oeuvre_categories (oeuvre_id INT NOT NULL, categorie_id INT NOT NULL, INDEX IDX_40808C0888194DE8 (oeuvre_id), INDEX IDX_40808C08BCF5E72D (categorie_id), PRIMARY KEY(oeuvre_id, categorie_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE oeuvre_categories ADD CONSTRAINT FK_40808C0888194DE8 FOREIGN KEY (oeuvre_id) REFERENCES oeuvre (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE oeuvre_categories ADD CONSTRAINT FK_40808C08BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE CASCADE');
        }
        
        // Vérifier si la table user existe avant de la supprimer
        $userTableExists = $this->connection->executeQuery(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'user'"
        )->fetchOne();
        
        if ($userTableExists) {
            $this->addSql('DROP TABLE user');
        }
        
        // Supprimer l'index s'il existe
        if ($this->indexExists('artiste', 'UNIQ_9C07354F989D9B62')) {
            $this->addSql('DROP INDEX UNIQ_9C07354F989D9B62 ON artiste');
        }
        
        // Modifier la table artiste
        if ($this->columnExists('artiste', 'slug')) {
            $this->addSql('ALTER TABLE artiste ADD image_size INT DEFAULT NULL, DROP slug');
        } else {
            // Si la colonne slug n'existe pas, on ajoute juste image_size
            if (!$this->columnExists('artiste', 'image_size')) {
                $this->addSql('ALTER TABLE artiste ADD image_size INT DEFAULT NULL');
            }
        }
        
        // Ajouter la nouvelle clé étrangère pour artiste
        if (!$this->foreignKeyExists('artiste', 'FK_9C07354FA76ED395')) {
            $this->addSql('ALTER TABLE artiste ADD CONSTRAINT FK_9C07354FA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        }
        
        // Supprimer la colonne type de categorie si elle existe
        if ($this->columnExists('categorie', 'type')) {
            $this->addSql('ALTER TABLE categorie DROP type');
        }
        
        // Ajouter la nouvelle clé étrangère pour notification
        if (!$this->foreignKeyExists('notification', 'FK_BF5476CAA76ED395')) {
            $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        }
        
        // Modifier la table oeuvre
        if ($this->columnExists('oeuvre', 'categories')) {
            if (!$this->columnExists('oeuvre', 'image_size')) {
                $this->addSql('ALTER TABLE oeuvre ADD image_size INT DEFAULT NULL, DROP categories, CHANGE image_path image_path VARCHAR(255) DEFAULT NULL');
            } else {
                $this->addSql('ALTER TABLE oeuvre DROP categories, CHANGE image_path image_path VARCHAR(255) DEFAULT NULL');
            }
        } else {
            if (!$this->columnExists('oeuvre', 'image_size')) {
                $this->addSql('ALTER TABLE oeuvre ADD image_size INT DEFAULT NULL, CHANGE image_path image_path VARCHAR(255) DEFAULT NULL');
            } else {
                $this->addSql('ALTER TABLE oeuvre CHANGE image_path image_path VARCHAR(255) DEFAULT NULL');
            }
        }
        
        // Supprimer avatar_url de utilisateur si elle existe
        if ($this->columnExists('utilisateur', 'avatar_url')) {
            $this->addSql('ALTER TABLE utilisateur DROP avatar_url');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, display_name VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, avatar_path VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, is_active TINYINT(1) DEFAULT 1 NOT NULL, is_verified TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE oeuvre_categories DROP FOREIGN KEY FK_40808C0888194DE8');
        $this->addSql('ALTER TABLE oeuvre_categories DROP FOREIGN KEY FK_40808C08BCF5E72D');
        $this->addSql('DROP TABLE oeuvre_categories');
        $this->addSql('ALTER TABLE artiste DROP FOREIGN KEY FK_9C07354FA76ED395');
        $this->addSql('ALTER TABLE artiste ADD slug VARCHAR(160) NOT NULL, DROP image_size');
        $this->addSql('ALTER TABLE artiste ADD CONSTRAINT FK_9C07354FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C07354F989D9B62 ON artiste (slug)');
        $this->addSql('ALTER TABLE categorie ADD type VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE oeuvre ADD categories JSON NOT NULL COMMENT \'(DC2Type:json)\', DROP image_size, CHANGE image_path image_path VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD avatar_url VARCHAR(500) DEFAULT NULL');
    }
}
