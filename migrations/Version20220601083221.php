<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220601083221 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `return` (id INT UNSIGNED AUTO_INCREMENT NOT NULL, battery_id INT UNSIGNED DEFAULT NULL, return_to INT DEFAULT NULL, return_from INT DEFAULT NULL, return_date DATETIME DEFAULT NULL, address LONGTEXT DEFAULT NULL, city VARCHAR(50) DEFAULT NULL, country VARCHAR(50) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_A79E3F0F19A19CFC (battery_id), INDEX IDX_A79E3F0F4ACAA341 (return_to), INDEX IDX_A79E3F0FD8EC1199 (return_from), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `return` ADD CONSTRAINT FK_A79E3F0F19A19CFC FOREIGN KEY (battery_id) REFERENCES battery (id)');
        $this->addSql('ALTER TABLE `return` ADD CONSTRAINT FK_A79E3F0F4ACAA341 FOREIGN KEY (return_to) REFERENCES user (id)');
        $this->addSql('ALTER TABLE `return` ADD CONSTRAINT FK_A79E3F0FD8EC1199 FOREIGN KEY (return_from) REFERENCES user (id)');
        $this->addSql('ALTER TABLE transaction_log ADD shipment_id INT UNSIGNED DEFAULT NULL, ADD return_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction_log ADD CONSTRAINT FK_747BDD0C7BE036FC FOREIGN KEY (shipment_id) REFERENCES shipment (id)');
        $this->addSql('ALTER TABLE transaction_log ADD CONSTRAINT FK_747BDD0C227416D5 FOREIGN KEY (return_id) REFERENCES `return` (id)');
        $this->addSql('CREATE INDEX IDX_747BDD0C7BE036FC ON transaction_log (shipment_id)');
        $this->addSql('CREATE INDEX IDX_747BDD0C227416D5 ON transaction_log (return_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction_log DROP FOREIGN KEY FK_747BDD0C227416D5');
        $this->addSql('DROP TABLE `return`');
        $this->addSql('ALTER TABLE transaction_log DROP FOREIGN KEY FK_747BDD0C7BE036FC');
        $this->addSql('DROP INDEX IDX_747BDD0C7BE036FC ON transaction_log');
        $this->addSql('DROP INDEX IDX_747BDD0C227416D5 ON transaction_log');
        $this->addSql('ALTER TABLE transaction_log DROP shipment_id, DROP return_id');
    }
}
