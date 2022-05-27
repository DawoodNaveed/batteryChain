<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220531072907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE manufacturers_recyclers (manufacturer_id INT UNSIGNED NOT NULL, recycler_id INT UNSIGNED NOT NULL, INDEX IDX_1551AB41A23B42D (manufacturer_id), INDEX IDX_1551AB41E07AB761 (recycler_id), PRIMARY KEY(manufacturer_id, recycler_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recycler (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, address LONGTEXT NOT NULL, contact VARCHAR(50) NOT NULL, city VARCHAR(50) NOT NULL, country VARCHAR(50) NOT NULL, status TINYINT(1) DEFAULT \'1\' NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_E6A430D7A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE manufacturers_recyclers ADD CONSTRAINT FK_1551AB41A23B42D FOREIGN KEY (manufacturer_id) REFERENCES manufacturer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE manufacturers_recyclers ADD CONSTRAINT FK_1551AB41E07AB761 FOREIGN KEY (recycler_id) REFERENCES recycler (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recycler ADD CONSTRAINT FK_E6A430D7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD recycler_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649E07AB761 FOREIGN KEY (recycler_id) REFERENCES recycler (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E07AB761 ON user (recycler_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE manufacturers_recyclers DROP FOREIGN KEY FK_1551AB41E07AB761');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649E07AB761');
        $this->addSql('DROP TABLE manufacturers_recyclers');
        $this->addSql('DROP TABLE recycler');
        $this->addSql('DROP INDEX UNIQ_8D93D649E07AB761 ON user');
        $this->addSql('ALTER TABLE user DROP recycler_id');
    }
}
