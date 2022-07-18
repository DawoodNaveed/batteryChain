<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220714080856 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction_log DROP FOREIGN KEY FK_747BDD0C227416D5');
        $this->addSql('ALTER TABLE transaction_log DROP FOREIGN KEY FK_747BDD0C7BE036FC');
        $this->addSql('DROP INDEX IDX_747BDD0C227416D5 ON transaction_log');
        $this->addSql('DROP INDEX IDX_747BDD0C7BE036FC ON transaction_log');
        $this->addSql('ALTER TABLE transaction_log ADD battery_id INT UNSIGNED DEFAULT NULL, ADD transaction_type VARCHAR(50) DEFAULT NULL, ADD status VARCHAR(50) DEFAULT NULL, DROP shipment_id, DROP return_id');
        $this->addSql('ALTER TABLE transaction_log ADD CONSTRAINT FK_747BDD0C19A19CFC FOREIGN KEY (battery_id) REFERENCES battery (id)');
        $this->addSql('CREATE INDEX IDX_747BDD0C19A19CFC ON transaction_log (battery_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction_log DROP FOREIGN KEY FK_747BDD0C19A19CFC');
        $this->addSql('DROP INDEX IDX_747BDD0C19A19CFC ON transaction_log');
        $this->addSql('ALTER TABLE transaction_log ADD return_id INT UNSIGNED DEFAULT NULL, DROP transaction_type, DROP status, CHANGE battery_id shipment_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction_log ADD CONSTRAINT FK_747BDD0C227416D5 FOREIGN KEY (return_id) REFERENCES battery_return (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE transaction_log ADD CONSTRAINT FK_747BDD0C7BE036FC FOREIGN KEY (shipment_id) REFERENCES shipment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_747BDD0C227416D5 ON transaction_log (return_id)');
        $this->addSql('CREATE INDEX IDX_747BDD0C7BE036FC ON transaction_log (shipment_id)');
    }
}
