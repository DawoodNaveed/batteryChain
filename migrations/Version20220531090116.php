<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220531090116 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE shipment (id INT UNSIGNED AUTO_INCREMENT NOT NULL, battery_id INT UNSIGNED DEFAULT NULL, shipment_to INT DEFAULT NULL, shipment_from INT DEFAULT NULL, shipment_date DATETIME DEFAULT NULL, address LONGTEXT DEFAULT NULL, city VARCHAR(50) DEFAULT NULL, country VARCHAR(50) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_2CB20DC19A19CFC (battery_id), INDEX IDX_2CB20DC135E8368 (shipment_to), INDEX IDX_2CB20DCA7C4F72E (shipment_from), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE shipment ADD CONSTRAINT FK_2CB20DC19A19CFC FOREIGN KEY (battery_id) REFERENCES battery (id)');
        $this->addSql('ALTER TABLE shipment ADD CONSTRAINT FK_2CB20DC135E8368 FOREIGN KEY (shipment_to) REFERENCES user (id)');
        $this->addSql('ALTER TABLE shipment ADD CONSTRAINT FK_2CB20DCA7C4F72E FOREIGN KEY (shipment_from) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE shipment');
    }
}
