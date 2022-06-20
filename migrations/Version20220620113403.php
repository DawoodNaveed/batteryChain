<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220620113403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E6A430D7E7927B63 ON battery (serial_number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_E6A430D7E7927B63 ON battery');
    }
}
