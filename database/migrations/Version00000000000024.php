<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000024 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE distribution_pickup_location (id BIGINT AUTO_INCREMENT NOT NULL, company_id BIGINT NOT NULL COMMENT \'公司id\', distributor_id BIGINT NOT NULL COMMENT \'所属店铺id\', rel_distributor_id BIGINT DEFAULT 0 NOT NULL COMMENT \'绑定店铺id\', name VARCHAR(255) NOT NULL COMMENT \'自提点名称\', lng VARCHAR(255) DEFAULT NULL COMMENT \'纬度\', lat VARCHAR(255) DEFAULT NULL COMMENT \'经度\', province VARCHAR(255) DEFAULT NULL COMMENT \'省\', city VARCHAR(255) DEFAULT NULL COMMENT \'市\', area VARCHAR(255) DEFAULT NULL COMMENT \'区\', address VARCHAR(255) DEFAULT NULL COMMENT \'地址\', contract_phone VARCHAR(20) NOT NULL COMMENT \'联系电话\', hours VARCHAR(255) DEFAULT NULL COMMENT \'营业时间\', workdays VARCHAR(255) DEFAULT \',\' COMMENT \'工作日：周一至周日->1-7，逗号分隔\', wait_pickup_days VARCHAR(255) DEFAULT \'0\' COMMENT \'最长预约时间，天\', latest_pickup_time VARCHAR(255) DEFAULT NULL COMMENT \'当前最晚提货时间\', created bigint NOT NULL, updated bigint NOT NULL, INDEX ix_distributor_id (distributor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'店铺自提点\' ');
        $this->addSql('CREATE TABLE orders_rel_ziti (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'ID\', order_id BIGINT NOT NULL COMMENT \'订单号\', company_id BIGINT NOT NULL COMMENT \'公司id\', name VARCHAR(255) NOT NULL COMMENT \'自提点名称\', lng VARCHAR(255) DEFAULT NULL COMMENT \'纬度\', lat VARCHAR(255) DEFAULT NULL COMMENT \'经度\', province VARCHAR(255) DEFAULT NULL COMMENT \'省\', city VARCHAR(255) DEFAULT NULL COMMENT \'市\', area VARCHAR(255) DEFAULT NULL COMMENT \'区\', address VARCHAR(255) DEFAULT NULL COMMENT \'地址\', contract_phone VARCHAR(20) NOT NULL COMMENT \'联系电话\', pickup_date VARCHAR(20) NOT NULL COMMENT \'自提日期\', pickup_time VARCHAR(20) NOT NULL COMMENT \'自提时间\', create_time INT NOT NULL COMMENT \'创建时间\', update_time INT DEFAULT NULL COMMENT \'更新时间\', INDEX idx_order_id (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'实体订单关联自提信息\' ');
        $this->addSql('ALTER TABLE orders_normal_orders ADD left_aftersales_num INT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'剩余可申请售后的数量\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
