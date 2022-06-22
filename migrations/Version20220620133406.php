<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220620133406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE battery_return DROP FOREIGN KEY FK_A79E3F0F4ACAA341');
        $this->addSql('ALTER TABLE battery_return CHANGE return_to return_to INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE battery_return ADD CONSTRAINT FK_A79E3F0F4ACAA341 FOREIGN KEY (return_to) REFERENCES recycler (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE battery_return DROP FOREIGN KEY FK_A79E3F0F4ACAA341');
        $this->addSql('ALTER TABLE battery_return CHANGE return_to return_to INT DEFAULT NULL');
        $this->addSql('ALTER TABLE battery_return ADD CONSTRAINT FK_A79E3F0F4ACAA341 FOREIGN KEY (return_to) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
