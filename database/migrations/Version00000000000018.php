<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000018 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE promotions_employee_purchase ADD minimum_amount INT UNSIGNED NOT NULL COMMENT \'起定金额，以元为单位\'');
        $this->addSql('ALTER TABLE refund_error_logs ADD distributor_id BIGINT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'分销商id\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
