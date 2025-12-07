<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251206192814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_9C07354F989D9B62 ON artiste');
        $this->addSql('ALTER TABLE artiste ADD social_links JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD image_size INT DEFAULT NULL, DROP slug, DROP reseaux_sociaux');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artiste ADD slug VARCHAR(160) NOT NULL, ADD reseaux_sociaux VARCHAR(255) DEFAULT NULL, DROP social_links, DROP image_size');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C07354F989D9B62 ON artiste (slug)');
    }
}
