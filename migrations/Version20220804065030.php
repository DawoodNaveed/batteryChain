<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220804065030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction_log ADD return_to INT UNSIGNED DEFAULT NULL, ADD from_user INT DEFAULT NULL, ADD address LONGTEXT DEFAULT NULL, ADD postal_code VARCHAR(50) DEFAULT NULL, ADD city VARCHAR(50) DEFAULT NULL, ADD country VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction_log ADD CONSTRAINT FK_747BDD0C4ACAA341 FOREIGN KEY (return_to) REFERENCES recycler (id)');
        $this->addSql('ALTER TABLE transaction_log ADD CONSTRAINT FK_747BDD0CF8050BAA FOREIGN KEY (from_user) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_747BDD0C4ACAA341 ON transaction_log (return_to)');
        $this->addSql('CREATE INDEX IDX_747BDD0CF8050BAA ON transaction_log (from_user)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction_log DROP FOREIGN KEY FK_747BDD0C4ACAA341');
        $this->addSql('ALTER TABLE transaction_log DROP FOREIGN KEY FK_747BDD0CF8050BAA');
        $this->addSql('DROP INDEX IDX_747BDD0C4ACAA341 ON transaction_log');
        $this->addSql('DROP INDEX IDX_747BDD0CF8050BAA ON transaction_log');
        $this->addSql('ALTER TABLE transaction_log DROP return_to, DROP from_user, DROP address, DROP postal_code, DROP city, DROP country');
    }
}
