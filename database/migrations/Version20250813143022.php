<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20250813143022 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // 添加税率字段到orders_invoice_item表
        $this->addSql('ALTER TABLE orders_invoice_item ADD COLUMN invoice_tax_rate VARCHAR(16) NULL COMMENT "发票税率，如13%" AFTER amount');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // 回滚：删除税率字段
        $this->addSql('ALTER TABLE orders_invoice_item DROP COLUMN invoice_tax_rate');
    }
}
