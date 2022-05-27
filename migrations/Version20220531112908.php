<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220531112908 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE transaction_log (id INT UNSIGNED AUTO_INCREMENT NOT NULL, transaction_hash VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6492D863A58');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649A23B42D');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649E07AB761');
        $this->addSql('DROP INDEX UNIQ_8D93D6492D863A58 ON user');
        $this->addSql('DROP INDEX UNIQ_8D93D649A23B42D ON user');
        $this->addSql('DROP INDEX UNIQ_8D93D649E07AB761 ON user');
        $this->addSql('ALTER TABLE user DROP manufacturer_id, DROP distributor_id, DROP recycler_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE transaction_log');
        $this->addSql('ALTER TABLE user ADD manufacturer_id INT UNSIGNED DEFAULT NULL, ADD distributor_id INT UNSIGNED DEFAULT NULL, ADD recycler_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6492D863A58 FOREIGN KEY (distributor_id) REFERENCES distributor (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649A23B42D FOREIGN KEY (manufacturer_id) REFERENCES manufacturer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649E07AB761 FOREIGN KEY (recycler_id) REFERENCES recycler (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6492D863A58 ON user (distributor_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649A23B42D ON user (manufacturer_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E07AB761 ON user (recycler_id)');
    }
}
