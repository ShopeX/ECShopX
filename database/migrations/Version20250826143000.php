<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20250826143000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // 添加运费使用的积分字段到 orders_normal_orders 表
        $this->addSql('ALTER TABLE orders_normal_orders ADD COLUMN freight_point INT DEFAULT 0 COMMENT "运费使用的积分"');
        
        // 添加运费使用的积分抵扣的金额字段到 orders_normal_orders 表
        $this->addSql('ALTER TABLE orders_normal_orders ADD COLUMN freight_point_fee INT DEFAULT 0 COMMENT "运费使用的积分抵扣的金额，以分为单位"');
        
        // // 为现有数据初始化新字段值
        // $this->addSql('UPDATE orders_normal_orders SET freight_point = 0, freight_point_fee = point_fee -  WHERE freight_point IS NULL OR freight_point_fee IS NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // 删除运费使用的积分抵扣的金额字段
        $this->addSql('ALTER TABLE orders_normal_orders DROP COLUMN freight_point_fee');
        
        // 删除运费使用的积分字段
        $this->addSql('ALTER TABLE orders_normal_orders DROP COLUMN freight_point');
    }
}
