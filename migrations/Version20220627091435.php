<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220627091435 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE battery ADD co2 VARCHAR(255) DEFAULT NULL, ADD production_date DATETIME DEFAULT NULL, ADD cell_type VARCHAR(255) DEFAULT NULL, ADD module_type VARCHAR(255) DEFAULT NULL, ADD acid_volume VARCHAR(255) DEFAULT NULL, ADD tray_number VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE battery DROP co2, DROP production_date, DROP cell_type, DROP module_type, DROP acid_volume, DROP tray_number');
    }
}
