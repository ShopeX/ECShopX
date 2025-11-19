<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000010 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE companys_protocol_update_log (log_id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'日志id\', company_id BIGINT NOT NULL COMMENT \'公司id\', type VARCHAR(30) NOT NULL COMMENT \'协议类型,privacy:隐私政策,member_register:注册协议\', content LONGTEXT NOT NULL COMMENT \'协议详细内容\', digest VARCHAR(64) NOT NULL COMMENT \'摘要\', created INT NOT NULL, PRIMARY KEY(log_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'协议更新日志表\' ');
        $this->addSql('CREATE TABLE members_protocol_log (log_id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'id\', company_id BIGINT NOT NULL COMMENT \'公司id\', user_id BIGINT NOT NULL COMMENT \'用户id\', digest VARCHAR(64) NOT NULL COMMENT \'摘要\', created bigint NOT NULL, INDEX idx_user_id (user_id), INDEX idx_company_id (company_id), PRIMARY KEY(log_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'会员隐私协议记录表\' ');
        $this->addSql('ALTER TABLE aftersales ADD is_partial_cancel TINYINT(1) DEFAULT \'0\' COMMENT \'是否部分取消订单退款\'');
        $this->addSql('ALTER TABLE distribution_distributor ADD is_require_building TINYINT(1) DEFAULT \'0\' COMMENT \'下单是否需要填写楼栋门牌号\'');
        $this->addSql('ALTER TABLE espier_subdistrict ADD province VARCHAR(255) DEFAULT NULL, ADD city VARCHAR(255) DEFAULT NULL, ADD area VARCHAR(255) DEFAULT NULL, ADD regions_id LONGTEXT DEFAULT NULL COMMENT \'国家行政区划编码组合，逗号隔开\', CHANGE label label VARCHAR(255) CHARACTER SET gbk COLLATE gbk_chinese_ci NOT NULL COMMENT \'地区名称\'');
        $this->addSql('ALTER TABLE orders_normal_orders ADD building_number VARCHAR(20) NOT NULL COMMENT \'楼栋号\', ADD house_number VARCHAR(20) NOT NULL COMMENT \'门牌号\'');
        $this->addSql('ALTER TABLE orders_normal_orders_items ADD cancel_item_num INT DEFAULT NULL COMMENT \'取消数量\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
