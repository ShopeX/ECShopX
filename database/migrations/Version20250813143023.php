<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20250813143023 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // 添加商品规格描述字段到orders_invoice_item表
        $this->addSql('ALTER TABLE orders_invoice_item ADD COLUMN item_spec_desc TEXT NULL COMMENT "商品规格描述" AFTER spec_info');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // 回滚：删除商品规格描述字段
        $this->addSql('ALTER TABLE orders_invoice_item DROP COLUMN item_spec_desc');
    }
}
