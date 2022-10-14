<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221006121516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE modified_battery (id INT UNSIGNED AUTO_INCREMENT NOT NULL, battery_id INT UNSIGNED DEFAULT NULL, manufacturer_id INT UNSIGNED DEFAULT NULL, modified_by INT DEFAULT NULL, action VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_CDE2660219A19CFC (battery_id), INDEX IDX_CDE26602A23B42D (manufacturer_id), INDEX IDX_CDE2660225F94802 (modified_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE modified_battery ADD CONSTRAINT FK_CDE2660219A19CFC FOREIGN KEY (battery_id) REFERENCES battery (id)');
        $this->addSql('ALTER TABLE modified_battery ADD CONSTRAINT FK_CDE26602A23B42D FOREIGN KEY (manufacturer_id) REFERENCES manufacturer (id)');
        $this->addSql('ALTER TABLE modified_battery ADD CONSTRAINT FK_CDE2660225F94802 FOREIGN KEY (modified_by) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE modified_battery DROP FOREIGN KEY FK_CDE2660219A19CFC');
        $this->addSql('ALTER TABLE modified_battery DROP FOREIGN KEY FK_CDE26602A23B42D');
        $this->addSql('ALTER TABLE modified_battery DROP FOREIGN KEY FK_CDE2660225F94802');
        $this->addSql('DROP TABLE modified_battery');
    }
}
