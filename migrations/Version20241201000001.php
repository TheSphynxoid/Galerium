<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour créer la table artiste avec les champs nom et prénom
 */
final class Version20241201000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table artiste avec les champs nom et prénom';
    }

    public function up(Schema $schema): void
    {
        // Création de la table artiste avec tous les champs de l'entité
        $table = $schema->createTable('artiste');
        
        // ID (clé primaire)
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
        
        // Email (unique)
        $table->addColumn('email', 'string', ['length' => 180]);
        $table->addUniqueIndex(['email'], 'UNIQ_9C07354FE7927C74');
        
        // Roles (JSON)
        $table->addColumn('roles', 'json');
        
        // Password
        $table->addColumn('password', 'string', ['length' => 255]);
        
        // Nom (obligatoire)
        $table->addColumn('nom', 'string', ['length' => 255, 'notnull' => true]);
        
        // Prénom (obligatoire)
        $table->addColumn('prenom', 'string', ['length' => 255, 'notnull' => true]);
        
        // Spécialité (optionnel)
        $table->addColumn('specialite', 'string', ['length' => 255, 'notnull' => false]);
        
        // Biographie (optionnel)
        $table->addColumn('biographie', 'text', ['notnull' => false]);
        
        // Site web (optionnel)
        $table->addColumn('site_web', 'string', ['length' => 255, 'notnull' => false]);
        
        // Réseaux sociaux (optionnel)
        $table->addColumn('reseaux_sociaux', 'string', ['length' => 255, 'notnull' => false]);
        
        // Photo de profil (optionnel)
        $table->addColumn('photo_profil', 'string', ['length' => 255, 'notnull' => false]);
        
        // Date de création
        $table->addColumn('created_at', 'datetime_immutable', ['notnull' => true]);
    }

    public function down(Schema $schema): void
    {
        // Suppression de la table artiste
        $schema->dropTable('artiste');
    }
}

