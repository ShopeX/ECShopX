<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20260626184356 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE company_shuyun_open_platform_config (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, company_id BIGINT NOT NULL, auth_value VARCHAR(128) DEFAULT NULL, plat_code VARCHAR(64) DEFAULT NULL, app_id VARCHAR(64) DEFAULT NULL, app_secret VARCHAR(512) DEFAULT NULL, access_token LONGTEXT DEFAULT NULL, is_over_due VARCHAR(8) DEFAULT NULL, is_enabled SMALLINT NOT NULL, created INT UNSIGNED NOT NULL COMMENT \'添加时间\', updated INT UNSIGNED DEFAULT NULL COMMENT \'更新时间\', UNIQUE INDEX uk_shuyun_op_auth_value (auth_value), UNIQUE INDEX uk_shuyun_op_company_id (company_id), UNIQUE INDEX uk_shuyun_op_app_id (app_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'数云开放网关租户配置\' ');
        $this->addSql('CREATE TABLE shuyun_offline_benefit (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, company_id BIGINT UNSIGNED NOT NULL, client_id VARCHAR(128) DEFAULT NULL, benefit_id VARCHAR(128) NOT NULL, benefit_name VARCHAR(512) DEFAULT NULL, effective_start INT UNSIGNED DEFAULT NULL COMMENT \'权益生效起(秒)\', effective_end INT UNSIGNED DEFAULT NULL COMMENT \'权益生效止(秒)\', claim_start INT UNSIGNED DEFAULT NULL COMMENT \'领取起(秒)\', claim_end INT UNSIGNED DEFAULT NULL COMMENT \'领取止(秒)\', condition_limits_json LONGTEXT DEFAULT NULL, local_card_id BIGINT UNSIGNED DEFAULT NULL COMMENT \'本地券模板/活动键\', created INT UNSIGNED NOT NULL COMMENT \'添加时间\', updated INT UNSIGNED DEFAULT NULL COMMENT \'更新时间\', UNIQUE INDEX uk_shuyun_offline_benefit_company_benefit (company_id, benefit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'数云线下权益档案\' ');
        $this->addSql('CREATE TABLE shuyun_offline_benefit_send_batch (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, company_id BIGINT UNSIGNED NOT NULL, request_id VARCHAR(128) NOT NULL, benefit_id VARCHAR(128) NOT NULL, send_kind VARCHAR(16) NOT NULL COMMENT \'single|batch\', send_time INT UNSIGNED DEFAULT NULL, expire_time INT UNSIGNED DEFAULT NULL, send_remark VARCHAR(512) DEFAULT NULL, status VARCHAR(32) NOT NULL, total_count INT UNSIGNED DEFAULT NULL, success_count INT UNSIGNED DEFAULT NULL, failure_count INT UNSIGNED DEFAULT NULL, report_pushed_at INT UNSIGNED DEFAULT NULL, report_last_error LONGTEXT DEFAULT NULL, report_retry_count SMALLINT UNSIGNED DEFAULT 0 NOT NULL, created INT UNSIGNED NOT NULL COMMENT \'添加时间\', updated INT UNSIGNED DEFAULT NULL COMMENT \'更新时间\', UNIQUE INDEX uk_shuyun_offline_benefit_batch_company_request (company_id, request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'数云线下权益发送批次\' ');
        $this->addSql('CREATE TABLE shuyun_offline_benefit_send_item (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, batch_id BIGINT UNSIGNED NOT NULL, customer_id VARCHAR(128) NOT NULL, member_user_id BIGINT UNSIGNED DEFAULT NULL, benefit_code VARCHAR(256) DEFAULT NULL, fail_reason LONGTEXT DEFAULT NULL, status VARCHAR(32) NOT NULL, send_time INT UNSIGNED DEFAULT NULL COMMENT \'实际发送时间(秒)\', send_reason VARCHAR(512) DEFAULT NULL, detail_pushed_at INT UNSIGNED DEFAULT NULL, last_consume_status VARCHAR(32) DEFAULT NULL COMMENT \'USED|NOT_USED 等\', last_consume_push_at INT UNSIGNED DEFAULT NULL, local_order_id BIGINT UNSIGNED DEFAULT NULL, created INT UNSIGNED NOT NULL COMMENT \'添加时间\', updated INT UNSIGNED DEFAULT NULL COMMENT \'更新时间\', INDEX IDX_41573DEDF39EBE7A (batch_id), UNIQUE INDEX uk_shuyun_offline_benefit_item_batch_customer (batch_id, customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'数云线下权益发送明细\' ');
        $this->addSql('CREATE TABLE shuyun_open_platform_traffic_audit (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, company_id BIGINT UNSIGNED NOT NULL, direction VARCHAR(16) NOT NULL, correlation_id VARCHAR(128) NOT NULL, http_verb VARCHAR(16) NOT NULL, action_method VARCHAR(255) DEFAULT NULL, http_status SMALLINT UNSIGNED DEFAULT NULL, outcome VARCHAR(32) NOT NULL, request_headers_json LONGTEXT NOT NULL, request_body LONGTEXT, response_body LONGTEXT, error_message VARCHAR(1024) DEFAULT NULL, created INT UNSIGNED NOT NULL COMMENT \'添加时间\', updated INT UNSIGNED DEFAULT NULL COMMENT \'更新时间\', INDEX idx_shuyun_op_traffic_company_created (company_id, created), INDEX idx_shuyun_op_traffic_correlation (correlation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'数云开放网关/回调排障审计（轻量）\' ');
        $this->addSql('CREATE TABLE supplier_items_attr_draft (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'ID\', company_id BIGINT NOT NULL COMMENT \'公司ID\', goods_id BIGINT NOT NULL COMMENT \'SPU ID\', item_id BIGINT NOT NULL COMMENT \'主表商品SKU ID\', attribute_id BIGINT DEFAULT 0 NOT NULL COMMENT \'商品属性id\', is_del BIGINT DEFAULT 0 NOT NULL COMMENT \'是否需要删除\', attribute_type VARCHAR(15) NOT NULL COMMENT \'商品属性类型\', attr_data LONGTEXT DEFAULT NULL COMMENT \'属性值\', created INT NOT NULL, updated INT DEFAULT NULL, INDEX ix_item_id (item_id), INDEX ix_goods_id (goods_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'供应商商品待审属性草稿表\' ');
        $this->addSql('ALTER TABLE shuyun_offline_benefit_send_item ADD CONSTRAINT FK_41573DEDF39EBE7A FOREIGN KEY (batch_id) REFERENCES shuyun_offline_benefit_send_batch (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE business_rep_user CHANGE create_time create_time bigint NOT NULL');
        $this->addSql('ALTER TABLE community_activity CHANGE activity_status activity_status VARCHAR(255) NOT NULL COMMENT \'活动状态 private私有 public公开 protected隐藏 success确认成团 fail成团失败\'');
        $this->addSql('ALTER TABLE distribution_distributor ADD is_platform_store_buy TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'是否开启云仓可购买（店务立即购买等）\', CHANGE is_valid is_valid VARCHAR(255) DEFAULT \'true\' NOT NULL COMMENT \'店铺状态：true启用云店 false禁用云店 closed闭店 delete撤店\'');
        $this->addSql('ALTER TABLE employee_purchase_activity_items ADD shelf_status SMALLINT DEFAULT 1 NOT NULL COMMENT \'上下架状态:1上架,0下架\'');
        $this->addSql('CREATE INDEX idx_activity_shelf ON employee_purchase_activity_items (activity_id, shelf_status)');
        $this->addSql('ALTER TABLE employee_purchase_relatives CHANGE created created bigint NOT NULL');
        $this->addSql('ALTER TABLE espier_uploadefile ADD relation_id BIGINT DEFAULT 0 NOT NULL COMMENT \'关联id\'');
        $this->addSql('ALTER TABLE kaquan_user_discount CHANGE card_id card_id BIGINT NOT NULL COMMENT \'微信用户领取的卡券 id \'');
        $this->addSql('ALTER TABLE members ADD shuyun_open_online_wxapp_sync_at INT UNSIGNED DEFAULT NULL COMMENT \'数云 OPEN 线上 wxapp 同步成功时间（Unix）；NULL 未成功\', ADD offline_reg_distributor INT UNSIGNED DEFAULT NULL COMMENT \'店务 OFFLINE member.register 成功时写入的分销商 ID；NULL 未写入\'');
        $this->addSql('ALTER TABLE members_tag_group_rel CHANGE created created bigint NOT NULL');
        $this->addSql('ALTER TABLE orders_normal_orders ADD uses_platform_item_stock SMALLINT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'是否云仓/平台SKU库存履约（店务立即购买等）\', ADD pos_payment_voucher_url VARCHAR(2048) DEFAULT NULL COMMENT \'POS支付凭证图片URL（可选）\'');
        $this->addSql('ALTER TABLE popularize_promoter_identity CHANGE is_default is_default INT NOT NULL COMMENT \'是否为默认\'');
        $this->addSql('ALTER TABLE promotions_package_item CHANGE is_show is_show TINYINT(1) DEFAULT NULL COMMENT \'列表页是否显示\'');
        $this->addSql('ALTER TABLE selfservice_registration_activity CHANGE pics pics TEXT');
        $this->addSql('ALTER TABLE theme_pc_template_content CHANGE name name VARCHAR(64) NOT NULL COMMENT \'配置名称\'');
        $this->addSql('ALTER TABLE user_signin_rules CHANGE days_required days_required  BIGINT NOT NULL COMMENT \'需要的天数\'');
        $this->addSql('ALTER TABLE web_menu_items CHANGE parent_id parent_id INT UNSIGNED DEFAULT 0 NOT NULL, CHANGE name name VARCHAR(100) NOT NULL, CHANGE image_url image_url VARCHAR(500) DEFAULT NULL, CHANGE link_type link_type VARCHAR(50) DEFAULT \'url\' NOT NULL, CHANGE link_value link_value VARCHAR(500) DEFAULT NULL, CHANGE link_extra link_extra LONGTEXT DEFAULT NULL, CHANGE status status SMALLINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE web_menus CHANGE name name VARCHAR(100) NOT NULL, CHANGE `key` `key` VARCHAR(100) NOT NULL, CHANGE status status SMALLINT DEFAULT 1 NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}