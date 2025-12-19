<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20251210140739 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE bspay_withdraw_apply (id BIGINT AUTO_INCREMENT NOT NULL, company_id BIGINT NOT NULL COMMENT \'公司ID\', merchant_id BIGINT DEFAULT 0 COMMENT \'商户ID\', distributor_id BIGINT DEFAULT 0 COMMENT \'店铺ID\', operator_type VARCHAR(20) DEFAULT \'\' NOT NULL COMMENT \'操作者类型：distributor=店铺, merchant=商户, admin=超级管理员, staff=员工\', operator_id BIGINT NOT NULL COMMENT \'操作账号ID\', huifu_id VARCHAR(255) DEFAULT \'\' COMMENT \'汇付ID\', amount INT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'申请金额（分）\', withdraw_type VARCHAR(10) DEFAULT \'T1\' NOT NULL COMMENT \'提现类型\', invoice_file VARCHAR(500) DEFAULT \'\' COMMENT \'发票文件路径\', status SMALLINT DEFAULT 0 NOT NULL COMMENT \'申请状态 0=审核中 1=审核通过 2=已拒绝 3=处理中 4=处理成功 5=处理失败 (参见WithdrawStatus枚举)\', audit_time INT DEFAULT NULL COMMENT \'审核时间\', auditor VARCHAR(100) DEFAULT \'\' COMMENT \'审核人\', auditor_operator_id BIGINT DEFAULT NULL COMMENT \'审核人操作账号ID\', audit_remark LONGTEXT DEFAULT NULL COMMENT \'审核备注\', hf_seq_id VARCHAR(128) DEFAULT \'\' COMMENT \'汇付全局流水号\', req_seq_id VARCHAR(128) DEFAULT \'\' COMMENT \'请求流水号\', request_time INT DEFAULT NULL COMMENT \'请求汇付时间\', failure_reason LONGTEXT DEFAULT NULL COMMENT \'失败原因\', operator VARCHAR(32) DEFAULT \'\' COMMENT \'申请人账号\', created INT NOT NULL, updated INT DEFAULT NULL COMMENT \'更新时间\', INDEX idx_company_id (company_id), INDEX idx_company_status (company_id, status), INDEX idx_operator (operator_type, operator_id), INDEX idx_status (status), INDEX idx_created (created), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoice_seller (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'自增id\', company_id BIGINT NOT NULL COMMENT \'公司id\', seller_name VARCHAR(64) NOT NULL COMMENT \'开票人\', payee VARCHAR(64) NOT NULL COMMENT \'收款人\', reviewer VARCHAR(64) NOT NULL COMMENT \'复核人\', seller_company_name VARCHAR(128) NOT NULL COMMENT \'销售方名称\', seller_tax_no VARCHAR(32) NOT NULL COMMENT \'销售方税号\', seller_bank_name VARCHAR(128) NOT NULL COMMENT \'销售方开户行\', seller_bank_account VARCHAR(64) NOT NULL COMMENT \'销售方银行账号\', seller_phone VARCHAR(32) NOT NULL COMMENT \'销售方电话\', seller_address VARCHAR(255) NOT NULL COMMENT \'销售方地址\', created_at INT NOT NULL COMMENT \'创建时间\', updated_at INT DEFAULT NULL COMMENT \'更新时间\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'发票销售方表\' ');
        $this->addSql('CREATE TABLE multi_lang_config (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'id\', company_id BIGINT NOT NULL COMMENT \'公司id\', table_name VARCHAR(255) DEFAULT \'\' NOT NULL COMMENT \'表名\', field VARCHAR(255) DEFAULT \'\' NOT NULL COMMENT \'field,字段名\', created INT NOT NULL, updated INT DEFAULT NULL, INDEX ix_company_id (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'多语言字典库\' ');
        $this->addSql('CREATE TABLE promotions_seckill_rel_category (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'关联id\', seckill_id BIGINT NOT NULL COMMENT \'秒杀活动id\', category_id BIGINT NOT NULL COMMENT \'分类id\', company_id BIGINT DEFAULT NULL COMMENT \'公司id\', category_level INT NOT NULL COMMENT \'分类等级\', INDEX idx_company_id (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'秒杀关联分类表\' ');
    
        $this->addSql('ALTER TABLE bspay_div_fee ADD merchant_id VARCHAR(255) DEFAULT NULL COMMENT \'商户ID，记录分账对象是商户时的商户ID\'');
        $this->addSql('CREATE INDEX ix_merchant ON bspay_div_fee (merchant_id)');
        $this->addSql('ALTER TABLE datacube_monitors ADD regionauth_id VARCHAR(255) DEFAULT NULL COMMENT \'区域ID\'');
        $this->addSql('ALTER TABLE kaquan_discount_cards ADD dm_card_id VARCHAR(255) DEFAULT NULL COMMENT \'达摩CRM卡券ID\', ADD dm_use_channel VARCHAR(255) DEFAULT NULL COMMENT \'达摩CRM适用渠道\'');
        $this->addSql('ALTER TABLE kaquan_user_discount ADD dm_card_code VARCHAR(100) DEFAULT NULL COMMENT \'达摩CRM会员卡券code\', CHANGE card_id card_id BIGINT NOT NULL COMMENT \'微信用户领取的卡券 id \'');
        $this->addSql('CREATE INDEX idx_dm_card_code ON kaquan_user_discount (dm_card_code)');
        $this->addSql('ALTER TABLE membercard_grade ADD dm_grade_code VARCHAR(50) DEFAULT \'\' COMMENT \'达摩CRM等级编码\', CHANGE grade_background grade_background VARCHAR(1024) DEFAULT NULL COMMENT \'等级背景\'');
        $this->addSql('ALTER TABLE members_info ADD dm_member_id VARCHAR(255) DEFAULT NULL COMMENT \'达摩CRM会员id\', ADD dm_card_no VARCHAR(255) DEFAULT NULL COMMENT \'达摩CRM会员卡号\', CHANGE created created bigint NOT NULL, CHANGE updated updated bigint NOT NULL');
        $this->addSql('CREATE INDEX idx_dm_card_no ON members_info (dm_card_no)');
        $this->addSql('ALTER TABLE orders_invoice ADD invoice_type_code VARCHAR(20) NOT NULL COMMENT \'开票类型编码，01:增值税专用发票,02:增值税普通发票\', ADD invoice_file_url_red VARCHAR(255) DEFAULT NULL COMMENT \'红票文件地址\', ADD end_time INT DEFAULT NULL COMMENT \'订单完成时间\', ADD close_aftersales_time INT DEFAULT NULL COMMENT \'售后截止时间\', ADD query_content JSON DEFAULT NULL COMMENT \'查询内容(DC2Type:json_array)\', ADD red_content JSON DEFAULT NULL COMMENT \'冲红内容(DC2Type:json_array)\', ADD serial_no VARCHAR(255) DEFAULT NULL COMMENT \'发票流水号\', ADD red_serial_no VARCHAR(255) DEFAULT NULL COMMENT \'红冲流水号\', ADD red_apply_bn VARCHAR(255) DEFAULT NULL COMMENT \'红冲申请单号\', ADD order_shop_id VARCHAR(20) DEFAULT NULL COMMENT \'订单店铺id\', ADD user_card_code VARCHAR(50) DEFAULT NULL COMMENT \'用户卡号\', CHANGE try_times try_times INT DEFAULT 0 NOT NULL COMMENT \'重试次数\'');
        $this->addSql('ALTER TABLE orders_invoice_item ADD original_num INT DEFAULT NULL COMMENT \'原始数量\', ADD original_amount INT DEFAULT NULL COMMENT \'原始金额，以分为单位\'');
        $this->addSql('ALTER TABLE orders_normal_orders ADD dm_point_preid VARCHAR(64) DEFAULT NULL COMMENT \'达摩积分预扣id\'');

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

    }
}
