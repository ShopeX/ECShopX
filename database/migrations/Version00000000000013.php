<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000013 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE community_chief_apply_info (apply_id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'申请id\', company_id BIGINT NOT NULL COMMENT \'公司ID\', distributor_id INT DEFAULT 0 NOT NULL COMMENT \'店铺id,为0时表示平台的团长申请\', user_id BIGINT NOT NULL COMMENT \'会员ID\', chief_name VARCHAR(255) NOT NULL COMMENT \'团长名称\', chief_mobile VARCHAR(255) NOT NULL COMMENT \'团长手机号\', extra_data LONGTEXT NOT NULL COMMENT \'附加信息\', approve_status INT DEFAULT 0 NOT NULL COMMENT \'审批状态 0:未审批 1:同意 2:驳回\', refuse_reason LONGTEXT DEFAULT NULL COMMENT \'拒绝原因\', created_at INT NOT NULL, updated_at INT DEFAULT NULL, INDEX ix_company_id (company_id), INDEX ix_chief_mobile (chief_mobile), INDEX ix_user_id (user_id), PRIMARY KEY(apply_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'社区拼团团长申请表\' ');
        $this->addSql('ALTER TABLE community_activity ADD distributor_id INT DEFAULT 0 NOT NULL COMMENT \'店铺id,为0时表示该活动为平台活动\', CHANGE activity_status activity_status VARCHAR(255) NOT NULL COMMENT \'活动状态 private私有 public公开 protected隐藏 success确认成团 fail成团失败\'');
        $this->addSql('ALTER TABLE community_activity_item CHANGE item_pics item_pics LONGTEXT DEFAULT NULL COMMENT \'商品图片\'');
        $this->addSql('ALTER TABLE config_request_fields ADD distributor_id INT DEFAULT 0 NOT NULL COMMENT \'店铺id,为0时表示该配置为平台创建\', CHANGE module_type module_type SMALLINT NOT NULL COMMENT \'模块类型, 【1: 会员注册】【2: 团长申请】\'');
        $this->addSql('ALTER TABLE orders_normal_orders ADD market_fee VARCHAR(255) DEFAULT NULL COMMENT \'销售价总金额，以分为单位\'');
        $this->addSql('ALTER TABLE pages_template_set CHANGE is_open_wechatapp_location is_open_wechatapp_location INT DEFAULT 1 COMMENT \'开启小程序定位 1开启 2关闭\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
