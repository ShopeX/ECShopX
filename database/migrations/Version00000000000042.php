<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000042 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE orders_rel_jushuitan (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'ID\', order_id BIGINT NOT NULL COMMENT \'订单号\', company_id BIGINT NOT NULL COMMENT \'公司id\', o_id BIGINT NOT NULL COMMENT \'聚水潭内部单号\', created INT NOT NULL COMMENT \'创建时间\', updated INT DEFAULT NULL COMMENT \'更新时间\', INDEX idx_company (company_id), INDEX idx_order_id (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'订单关联聚水潭表\' ');
        $this->addSql('ALTER TABLE aftersales ADD freight INT UNSIGNED DEFAULT 0 COMMENT \'退款运费\'');
        $this->addSql('ALTER TABLE aftersales_refund ADD freight INT UNSIGNED DEFAULT 0 COMMENT \'退款运费\'');
        $this->addSql('CREATE INDEX idx_supplier_id ON aftersales_refund (supplier_id)');
        $this->addSql('ALTER TABLE distribution_distributor ADD is_refund_freight INT DEFAULT 0 COMMENT \'退款退货可退运费 1是 0否\', ADD wdt_shop_no VARCHAR(30) DEFAULT NULL COMMENT \'旺店通门店编号\', ADD wdt_shop_id BIGINT DEFAULT 0 NOT NULL COMMENT \'旺店通门店ID\', ADD jst_shop_id BIGINT DEFAULT 0 NOT NULL COMMENT \'聚水潭店铺编号\'');
        $this->addSql('ALTER TABLE orders_normal_orders_items ADD goods_id BIGINT NOT NULL COMMENT \'产品id\'');
        $this->addSql('ALTER TABLE refund_error_logs ADD supplier_id BIGINT DEFAULT 0 NOT NULL COMMENT \'供应商id\'');
        $this->addSql('CREATE INDEX idx_supplier_id ON refund_error_logs (supplier_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
