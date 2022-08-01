<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220801062108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE battery_return ADD transaction_log_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE battery_return ADD CONSTRAINT FK_9F9F034C9C1A2FC6 FOREIGN KEY (transaction_log_id) REFERENCES transaction_log (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9F9F034C9C1A2FC6 ON battery_return (transaction_log_id)');
        $this->addSql('ALTER TABLE shipment ADD transaction_log_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE shipment ADD CONSTRAINT FK_2CB20DC9C1A2FC6 FOREIGN KEY (transaction_log_id) REFERENCES transaction_log (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2CB20DC9C1A2FC6 ON shipment (transaction_log_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE battery_return DROP FOREIGN KEY FK_9F9F034C9C1A2FC6');
        $this->addSql('DROP INDEX UNIQ_9F9F034C9C1A2FC6 ON battery_return');
        $this->addSql('ALTER TABLE battery_return DROP transaction_log_id');
        $this->addSql('ALTER TABLE shipment DROP FOREIGN KEY FK_2CB20DC9C1A2FC6');
        $this->addSql('DROP INDEX UNIQ_2CB20DC9C1A2FC6 ON shipment');
        $this->addSql('ALTER TABLE shipment DROP transaction_log_id');
    }
}
