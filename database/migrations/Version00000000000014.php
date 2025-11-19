<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use AdaPayBundle\Services\AlipayIndustryCategoryService;

class Version00000000000014 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE adapay_alipay_industry_category (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'分类id\', category_name VARCHAR(100) NOT NULL COMMENT \'分类名称\', parent_id BIGINT DEFAULT 0 NOT NULL COMMENT \'父级id, 0为顶级\', category_level INT DEFAULT 1 NOT NULL COMMENT \'分类等级\', alipay_cls_id BIGINT DEFAULT NULL COMMENT \'行业分类ID\', alipay_category_id VARCHAR(20) DEFAULT NULL COMMENT \'支付宝经营类目\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'支付宝行业分类\' ');
        $this->addSql('CREATE TABLE community_chief_cash_withdrawal (id BIGINT AUTO_INCREMENT NOT NULL, company_id BIGINT NOT NULL COMMENT \'公司id\', distributor_id INT DEFAULT 0 NOT NULL COMMENT \'店铺id,为0时表示平台的团长申请\', chief_id BIGINT NOT NULL COMMENT \'团长ID\', account_name VARCHAR(255) DEFAULT NULL COMMENT \'提现账号姓名\', pay_account VARCHAR(255) NOT NULL COMMENT \'提现账号 微信为openid 支付宝为支付宝账号 银行卡为银行卡号\', bank_name VARCHAR(255) DEFAULT NULL COMMENT \'银行名称\', mobile VARCHAR(255) NOT NULL COMMENT \'手机号\', money INT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'提现金额，以分为单位\', status VARCHAR(255) NOT NULL COMMENT \'提现状态：apply->待处理 reject->拒绝 success->提现成功 process->处理中 failed->提现失败\', remarks VARCHAR(255) DEFAULT NULL COMMENT \'备注\', pay_type VARCHAR(255) NOT NULL COMMENT \'提现支付类型\', wxa_appid VARCHAR(255) NOT NULL COMMENT \'提现的小程序appid\', created bigint NOT NULL, updated bigint NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'团长提现表\' ');
        $this->addSql('ALTER TABLE community_activity CHANGE activity_status activity_status VARCHAR(255) NOT NULL COMMENT \'活动状态 private私有 public公开 protected隐藏 success确认成团 fail成团失败\'');
        $this->addSql('ALTER TABLE community_chief ADD alipay_name VARCHAR(255) DEFAULT NULL COMMENT \'团长提现的支付宝姓名\', ADD alipay_account VARCHAR(255) DEFAULT NULL COMMENT \'团长提现的支付宝账号\', ADD bank_name VARCHAR(255) DEFAULT NULL COMMENT \'银行名称\', ADD bankcard_no VARCHAR(255) DEFAULT NULL COMMENT \'团长提现的银行卡号\'');
        $this->addSql('ALTER TABLE community_order_rel_activity ADD rebate_ratio VARCHAR(20) DEFAULT \'0\' NOT NULL COMMENT \'佣金比例\'');
        $this->addSql('ALTER TABLE companys CHANGE menu_type menu_type INT DEFAULT 3 NOT NULL COMMENT \'菜单类型。2:\'\'b2c\'\',3:\'\'platform\'\',4:\'\'standard\'\',5:\'\'in_purchase\'\'\'');
        $this->addSql('ALTER TABLE membercard_grade ADD description LONGTEXT DEFAULT NULL COMMENT \'详细说明\'');
        $this->addSql('UPDATE operators SET is_dealer_main=0 where is_dealer_main=""');
        $this->addSql('ALTER TABLE operators ADD is_distributor_main TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'是否是店铺超级管理员.1:是,0:否\', DROP lastloginip, CHANGE head_portrait head_portrait VARCHAR(255) DEFAULT NULL COMMENT \'头像\', CHANGE is_disable is_disable TINYINT(1) DEFAULT \'0\' COMMENT \'是否禁用。1:是 0:否\', CHANGE is_dealer_main is_dealer_main TINYINT(1) DEFAULT \'1\' NOT NULL COMMENT \'是否是经销商主账号。1:是,0:否\', CHANGE is_merchant_main is_merchant_main TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'是否是商户端超级管理员。1:是,0:否\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
