<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250917120500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pef_id to pay_trans_transactions table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE metier.pay_trans_transactions ADD pef_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE metier.pay_trans_transactions ADD CONSTRAINT FK_TRANSACTION_ATTRIBUTION FOREIGN KEY (pef_id) REFERENCES metier.attribution (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_TRANSACTION_ATTRIBUTION ON metier.pay_trans_transactions (pef_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE metier.pay_trans_transactions DROP CONSTRAINT FK_TRANSACTION_ATTRIBUTION');
        $this->addSql('DROP INDEX IDX_TRANSACTION_ATTRIBUTION');
        $this->addSql('ALTER TABLE metier.pay_trans_transactions DROP pef_id');
    }
}
