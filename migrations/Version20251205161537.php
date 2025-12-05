<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205161537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE participation_concours (participation_id INT NOT NULL, concours_id INT NOT NULL, INDEX IDX_A386DD016ACE3B73 (participation_id), INDEX IDX_A386DD01D11E3C7 (concours_id), PRIMARY KEY(participation_id, concours_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE participation_concours ADD CONSTRAINT FK_A386DD016ACE3B73 FOREIGN KEY (participation_id) REFERENCES participation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participation_concours ADD CONSTRAINT FK_A386DD01D11E3C7 FOREIGN KEY (concours_id) REFERENCES concours (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participation_concours DROP FOREIGN KEY FK_A386DD016ACE3B73');
        $this->addSql('ALTER TABLE participation_concours DROP FOREIGN KEY FK_A386DD01D11E3C7');
        $this->addSql('DROP TABLE participation_concours');
    }
}
