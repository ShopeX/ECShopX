<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20260518000001 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE theme_pc_template ADD distributor_id INT DEFAULT 0 NOT NULL COMMENT \'店铺ID\'');
        $this->addSql('CREATE INDEX idx_company_distributor ON theme_pc_template (company_id, distributor_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX idx_company_distributor ON theme_pc_template');
        $this->addSql('ALTER TABLE theme_pc_template DROP distributor_id');
    }
}
