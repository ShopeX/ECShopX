<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000043 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE offline_bank_account (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'id\', company_id BIGINT NOT NULL COMMENT \'公司id\', bank_account_name VARCHAR(50) NOT NULL COMMENT \'收款账户名称\', bank_account_no VARCHAR(30) NOT NULL COMMENT \'银行账号\', bank_name VARCHAR(100) NOT NULL COMMENT \'开户银行\', china_ums_no VARCHAR(20) NOT NULL COMMENT \'银联号\', pic VARCHAR(255) NOT NULL COMMENT \'图片\', remark VARCHAR(255) NOT NULL COMMENT \'备注\', is_default TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'是否默认\', created bigint NOT NULL, updated bigint NOT NULL, INDEX idx_company_id (company_id), INDEX idx_is_default (is_default), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'线下转账收款账户\' ');
        $this->addSql('CREATE TABLE offline_payment (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'自增ID\', order_id BIGINT NOT NULL COMMENT \'订单号\', company_id BIGINT NOT NULL COMMENT \'公司id\', user_id BIGINT NOT NULL COMMENT \'用户id\', shop_id BIGINT DEFAULT 0 COMMENT \'门店id\', distributor_id BIGINT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'分销商id\', total_fee BIGINT UNSIGNED NOT NULL COMMENT \'订单金额，以分为单位\', pay_fee BIGINT UNSIGNED NOT NULL COMMENT \'支付金额，以分为单位\', check_status SMALLINT DEFAULT 0 NOT NULL COMMENT \'审核状态。可选值有 0 待处理;1 已审核;2 已拒绝;9 已取消\', bank_account_id BIGINT NOT NULL COMMENT \'收款账户id\', bank_account_name VARCHAR(50) NOT NULL COMMENT \'收款账户名称\', bank_account_no VARCHAR(30) NOT NULL COMMENT \'银行账号\', bank_name VARCHAR(100) NOT NULL COMMENT \'开户银行\', china_ums_no VARCHAR(20) NOT NULL COMMENT \'银联号\', pay_account_name VARCHAR(100) DEFAULT \'\' COMMENT \'付款账户名\', pay_account_bank VARCHAR(100) DEFAULT \'\' COMMENT \'付款银行\', pay_account_no VARCHAR(100) DEFAULT \'\' COMMENT \'付款账号\', pay_sn VARCHAR(100) DEFAULT \'\' COMMENT \'付款流水单号\', voucher_pic JSON NOT NULL COMMENT \'付款凭证图片(DC2Type:json_array)\', transfer_remark VARCHAR(100) DEFAULT \'\' COMMENT \'转账备注\', operator_name VARCHAR(50) DEFAULT \'\' COMMENT \'审核人\', remark VARCHAR(500) DEFAULT \'\' COMMENT \'审核备注\', create_time INT NOT NULL COMMENT \'创建时间\', update_time INT DEFAULT NULL COMMENT \'更新时间\', INDEX idx_order_id (order_id), INDEX idx_company_id (company_id), INDEX idx_user_id (user_id), INDEX idx_create_time (create_time), INDEX idx_check_status (check_status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'线下转账支付订单\' ');
        $this->addSql('ALTER TABLE aliyunsms_sign ADD third_party INT DEFAULT 0 NOT NULL COMMENT \'签名用途 1:他用;0:自用\', ADD qualification_id VARCHAR(255) DEFAULT NULL COMMENT \'资质ID\'');
        $this->addSql('ALTER TABLE aliyunsms_template ADD related_sign_name VARCHAR(20) NOT NULL COMMENT \'关联签名名称\'');
        $this->addSql('ALTER TABLE bspay_div_fee ADD supplier_id BIGINT DEFAULT 0 NOT NULL COMMENT \'供应商ID\', CHANGE operator_type operator_type VARCHAR(255) NOT NULL COMMENT \'操作者类型:distributor-店铺;dealer-经销;admin:超级管理员;supplier:经销商\'');
        $this->addSql('CREATE INDEX ix_supplier_id ON bspay_div_fee (supplier_id)');
        $this->addSql('ALTER TABLE espier_uploadimages ADD merchant_id BIGINT DEFAULT 0 NOT NULL COMMENT \'商户id\'');
        $this->addSql('ALTER TABLE espier_uploadimages_cat ADD distributor_id BIGINT DEFAULT 0 COMMENT \'店铺id\', ADD merchant_id BIGINT DEFAULT 0 NOT NULL COMMENT \'商户id\'');
        $this->addSql('ALTER TABLE statement_details ADD supplier_id BIGINT DEFAULT 0 NOT NULL COMMENT \'供应商ID\'');
        $this->addSql('CREATE INDEX idx_statement_id ON statement_details (statement_id)');
        $this->addSql('CREATE INDEX idx_supplier_id ON statement_details (supplier_id)');
        $this->addSql('CREATE INDEX idx_created ON statement_details (created)');
        $this->addSql('ALTER TABLE statement_period_setting ADD supplier_id BIGINT NOT NULL COMMENT \'供应商ID\', ADD merchant_type VARCHAR(30) DEFAULT \'distributor\' COMMENT \'商户类型：distributor 经销商,supplier 供应商\'');
        $this->addSql('CREATE INDEX idx_supplier_id ON statement_period_setting (supplier_id)');
        $this->addSql('CREATE INDEX idx_distributor_id ON statement_period_setting (distributor_id)');
        $this->addSql('ALTER TABLE statements ADD supplier_id BIGINT DEFAULT 0 NOT NULL COMMENT \'供应商ID\', ADD merchant_type VARCHAR(30) DEFAULT \'distributor\' COMMENT \'商户类型：distributor 经销商,supplier 供应商\'');
        $this->addSql('CREATE INDEX idx_merchant_type ON statements (merchant_type)');
        $this->addSql('CREATE INDEX idx_supplier_id ON statements (supplier_id)');
        $this->addSql('CREATE INDEX idx_distributor_id ON statements (distributor_id)');
        $this->addSql('CREATE INDEX idx_start_time ON statements (start_time)');
        $this->addSql('ALTER TABLE supplier_items CHANGE goods_bn goods_bn VARCHAR(255) DEFAULT NULL COMMENT \'SPU编码\'');
        $this->addSql('ALTER TABLE supplier_order ADD is_settled TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'是否分账(斗拱)\'');
        $this->addSql('ALTER TABLE orders_normal_orders ADD offline_payment_status SMALLINT DEFAULT -1 COMMENT \'线下支付状态：-1-未上传转账凭证；0-待处理；1-已审核；2-已拒绝；9-已取消\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
