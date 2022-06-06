<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220531063907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE distributor (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, address LONGTEXT NOT NULL, contact VARCHAR(50) NOT NULL, city VARCHAR(50) NOT NULL, country VARCHAR(50) NOT NULL, status TINYINT(1) DEFAULT \'1\' NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_A3C55771A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE manufacturer (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, address LONGTEXT NOT NULL, contact VARCHAR(50) NOT NULL, city VARCHAR(50) NOT NULL, country VARCHAR(50) NOT NULL, status TINYINT(1) DEFAULT \'1\' NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_3D0AE6DCA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE manufacturers_distributors (manufacturer_id INT UNSIGNED NOT NULL, distributor_id INT UNSIGNED NOT NULL, INDEX IDX_A70DFB38A23B42D (manufacturer_id), INDEX IDX_A70DFB382D863A58 (distributor_id), PRIMARY KEY(manufacturer_id, distributor_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE distributor ADD CONSTRAINT FK_A3C55771A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE manufacturer ADD CONSTRAINT FK_3D0AE6DCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE manufacturers_distributors ADD CONSTRAINT FK_A70DFB38A23B42D FOREIGN KEY (manufacturer_id) REFERENCES manufacturer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE manufacturers_distributors ADD CONSTRAINT FK_A70DFB382D863A58 FOREIGN KEY (distributor_id) REFERENCES distributor (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD manufacturer_id INT UNSIGNED DEFAULT NULL, ADD distributor_id INT UNSIGNED DEFAULT NULL, ADD first_name VARCHAR(255) NOT NULL, ADD last_name VARCHAR(255) NOT NULL, ADD created DATETIME DEFAULT NULL, ADD updated DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649A23B42D FOREIGN KEY (manufacturer_id) REFERENCES manufacturer (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6492D863A58 FOREIGN KEY (distributor_id) REFERENCES distributor (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649A23B42D ON user (manufacturer_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6492D863A58 ON user (distributor_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE manufacturers_distributors DROP FOREIGN KEY FK_A70DFB382D863A58');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6492D863A58');
        $this->addSql('ALTER TABLE manufacturers_distributors DROP FOREIGN KEY FK_A70DFB38A23B42D');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649A23B42D');
        $this->addSql('DROP TABLE distributor');
        $this->addSql('DROP TABLE manufacturer');
        $this->addSql('DROP TABLE manufacturers_distributors');
        $this->addSql('DROP INDEX UNIQ_8D93D649A23B42D ON user');
        $this->addSql('DROP INDEX UNIQ_8D93D6492D863A58 ON user');
        $this->addSql('ALTER TABLE user DROP manufacturer_id, DROP distributor_id, DROP first_name, DROP last_name, DROP created, DROP updated');
    }
}
