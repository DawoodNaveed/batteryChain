<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220620064937 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE manufacturers_distributors DROP FOREIGN KEY FK_A70DFB382D863A58');
        $this->addSql('DROP TABLE distributor');
        $this->addSql('DROP TABLE manufacturers_distributors');
        $this->addSql('ALTER TABLE recycler DROP FOREIGN KEY FK_E6A430D7A76ED395');
        $this->addSql('DROP INDEX UNIQ_E6A430D7A76ED395 ON recycler');
        $this->addSql('ALTER TABLE recycler ADD email VARCHAR(180) NOT NULL, DROP user_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E6A430D7E7927C74 ON recycler (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE distributor (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, address LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, contact VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, city VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, country VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, status TINYINT(1) DEFAULT \'1\' NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_A3C55771A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE manufacturers_distributors (manufacturer_id INT UNSIGNED NOT NULL, distributor_id INT UNSIGNED NOT NULL, INDEX IDX_A70DFB382D863A58 (distributor_id), INDEX IDX_A70DFB38A23B42D (manufacturer_id), PRIMARY KEY(manufacturer_id, distributor_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE distributor ADD CONSTRAINT FK_A3C55771A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE manufacturers_distributors ADD CONSTRAINT FK_A70DFB382D863A58 FOREIGN KEY (distributor_id) REFERENCES distributor (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE manufacturers_distributors ADD CONSTRAINT FK_A70DFB38A23B42D FOREIGN KEY (manufacturer_id) REFERENCES manufacturer (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('DROP INDEX UNIQ_E6A430D7E7927C74 ON recycler');
        $this->addSql('ALTER TABLE recycler ADD user_id INT DEFAULT NULL, DROP email');
        $this->addSql('ALTER TABLE recycler ADD CONSTRAINT FK_E6A430D7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E6A430D7A76ED395 ON recycler (user_id)');
    }
}
