<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220615114400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE battery_type (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(50) DEFAULT NULL, status TINYINT(1) DEFAULT \'1\' NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE battery ADD battery_type_id INT UNSIGNED DEFAULT NULL, DROP battery_type');
        $this->addSql('ALTER TABLE battery ADD CONSTRAINT FK_D02EF4AE2AA19D85 FOREIGN KEY (battery_type_id) REFERENCES battery_type (id)');
        $this->addSql('CREATE INDEX IDX_D02EF4AE2AA19D85 ON battery (battery_type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE battery DROP FOREIGN KEY FK_D02EF4AE2AA19D85');
        $this->addSql('DROP TABLE battery_type');
        $this->addSql('DROP INDEX IDX_D02EF4AE2AA19D85 ON battery');
        $this->addSql('ALTER TABLE battery ADD battery_type VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP battery_type_id');
    }
}
