<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127174155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE concours_utilisateur (concours_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_CFC902E1D11E3C7 (concours_id), INDEX IDX_CFC902E1FB88E14F (utilisateur_id), PRIMARY KEY(concours_id, utilisateur_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE concours_utilisateur ADD CONSTRAINT FK_CFC902E1D11E3C7 FOREIGN KEY (concours_id) REFERENCES concours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE concours_utilisateur ADD CONSTRAINT FK_CFC902E1FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE concours_utilisateur DROP FOREIGN KEY FK_CFC902E1D11E3C7');
        $this->addSql('ALTER TABLE concours_utilisateur DROP FOREIGN KEY FK_CFC902E1FB88E14F');
        $this->addSql('DROP TABLE concours_utilisateur');
    }
}
