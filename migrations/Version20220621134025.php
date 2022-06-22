<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220621134025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE battery ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE battery_return ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE battery_type ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE country ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE manufacturer ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE recycler ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE shipment ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction_log ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD deleted_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE battery DROP deleted_at');
        $this->addSql('ALTER TABLE battery_return DROP deleted_at');
        $this->addSql('ALTER TABLE battery_type DROP deleted_at');
        $this->addSql('ALTER TABLE country DROP deleted_at');
        $this->addSql('ALTER TABLE manufacturer DROP deleted_at');
        $this->addSql('ALTER TABLE recycler DROP deleted_at');
        $this->addSql('ALTER TABLE shipment DROP deleted_at');
        $this->addSql('ALTER TABLE transaction_log DROP deleted_at');
        $this->addSql('ALTER TABLE user DROP deleted_at');
    }
}
