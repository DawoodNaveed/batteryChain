<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220826103455 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bulk_import (id INT UNSIGNED AUTO_INCREMENT NOT NULL, manufacturer_id INT UNSIGNED DEFAULT NULL, csv VARCHAR(255) DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_BD98E874A23B42D (manufacturer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bulk_import ADD CONSTRAINT FK_BD98E874A23B42D FOREIGN KEY (manufacturer_id) REFERENCES manufacturer (id)');
        $this->addSql('ALTER TABLE battery ADD import_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE battery ADD CONSTRAINT FK_D02EF4AEB6A263D9 FOREIGN KEY (import_id) REFERENCES bulk_import (id)');
        $this->addSql('CREATE INDEX IDX_D02EF4AEB6A263D9 ON battery (import_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE battery DROP FOREIGN KEY FK_D02EF4AEB6A263D9');
        $this->addSql('ALTER TABLE bulk_import DROP FOREIGN KEY FK_BD98E874A23B42D');
        $this->addSql('DROP TABLE bulk_import');
        $this->addSql('DROP INDEX IDX_D02EF4AEB6A263D9 ON battery');
        $this->addSql('ALTER TABLE battery DROP import_id');
    }
}
