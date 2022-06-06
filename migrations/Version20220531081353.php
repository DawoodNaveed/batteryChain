<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220531081353 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE battery ADD serial_number VARCHAR(255) DEFAULT NULL, ADD battery_type VARCHAR(50) DEFAULT NULL, ADD current_owner INT UNSIGNED DEFAULT NULL, ADD nominal_voltage DOUBLE PRECISION UNSIGNED DEFAULT NULL, ADD nominal_capacity DOUBLE PRECISION UNSIGNED DEFAULT NULL, ADD nominal_energy DOUBLE PRECISION UNSIGNED DEFAULT NULL, ADD cycle_life INT UNSIGNED DEFAULT NULL, ADD height DOUBLE PRECISION UNSIGNED DEFAULT NULL, ADD width DOUBLE PRECISION UNSIGNED DEFAULT NULL, ADD mass DOUBLE PRECISION UNSIGNED DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD status TINYINT(1) DEFAULT \'1\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE battery DROP serial_number, DROP battery_type, DROP current_owner, DROP nominal_voltage, DROP nominal_capacity, DROP nominal_energy, DROP cycle_life, DROP height, DROP width, DROP mass, CHANGE status status TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE user DROP status');
    }
}
