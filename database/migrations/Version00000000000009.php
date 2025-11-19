<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000009 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE espier_subdistrict (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'id\', company_id BIGINT NOT NULL COMMENT \'公司company id\', label VARCHAR(255) NOT NULL COMMENT \'地区名称\', parent_id BIGINT NOT NULL COMMENT \'父级id\', distributor_id VARCHAR(255) DEFAULT \',\' NOT NULL COMMENT \'所属店铺id列表\', INDEX ix_parent_id (parent_id), INDEX ix_label (label), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'街道社区表\' ');
        $this->addSql('CREATE TABLE order_epidemic_register (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'ID\', order_id BIGINT NOT NULL COMMENT \'订单id\', user_id BIGINT NOT NULL COMMENT \'用户ID\', company_id INT NOT NULL COMMENT \'公司id\', distributor_id BIGINT NOT NULL COMMENT \'店铺ID\', name LONGTEXT NOT NULL COMMENT \'登记姓名\', mobile LONGTEXT NOT NULL COMMENT \'手机号\', cert_id LONGTEXT NOT NULL COMMENT \'身份证号\', temperature VARCHAR(30) NOT NULL COMMENT \'体温\', job VARCHAR(100) NOT NULL COMMENT \'职业\', symptom VARCHAR(50) NOT NULL COMMENT \'症状\', symptom_des VARCHAR(500) DEFAULT NULL COMMENT \'症状描述\', is_risk_area INT NOT NULL COMMENT \'是否去过中高风险地区 1:是 0:否\', is_use INT DEFAULT 1 NOT NULL COMMENT \'是否使用这条登记信息 1:是 0:否\', order_time INT NOT NULL COMMENT \'下单时间\', created INT NOT NULL, updated INT DEFAULT NULL, INDEX idx_company_id (company_id), INDEX idx_distributor_id (distributor_id), INDEX idx_order_id (order_id), INDEX idx_user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'订单疫情登记表\' ');
        $this->addSql('CREATE TABLE shop_menu_rel_type (rel_id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'关联ID\', shopmenu_id BIGINT NOT NULL COMMENT \'菜单id\', menu_type INT DEFAULT 1 NOT NULL COMMENT \'菜单类型\', company_id INT NOT NULL COMMENT \'公司ID\', created INT NOT NULL, INDEX ix_shopmenu_id (shopmenu_id), PRIMARY KEY(rel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'菜单类型关联\' ');
        $this->addSql('ALTER TABLE companys ADD menu_type INT DEFAULT 0 NOT NULL COMMENT \'菜单类型\'');
        $this->addSql('CREATE INDEX idx_salespersonid_adddate_companyid_statistictitle_statistictype ON companys_salesperson_statistics (salesperson_id, add_date, company_id, statistic_title, statistic_type)');
        $this->addSql('ALTER TABLE distribution_distributor ADD is_require_subdistrict TINYINT(1) DEFAULT \'0\' COMMENT \'下单是否需要选择街道社区\'');
        $this->addSql('CREATE INDEX idx_defaultitemid_companyid ON distribution_distributor_items (default_item_id, company_id)');
        $this->addSql('CREATE INDEX idx_companyid_istotalstore_goodscansale_defaultitemid ON distribution_distributor_items (company_id, is_total_store, goods_can_sale, default_item_id)');
        $this->addSql('ALTER TABLE items ADD is_epidemic INT DEFAULT 0 COMMENT \'是否为疫情需要登记的商品  1:是 0:否\'');
        $this->addSql('CREATE INDEX ix_item_bn ON items (item_bn)');
        $this->addSql('CREATE UNIQUE INDEX idx_passportuid ON operators (passport_uid)');
        $this->addSql('CREATE INDEX idx_orderid_userid_companyid ON orders_items_rel_profit (order_id, user_id, company_id)');
        $this->addSql('DROP INDEX idx_order_status ON orders_normal_orders');
        $this->addSql('ALTER TABLE orders_normal_orders ADD subdistrict_parent_id BIGINT NOT NULL COMMENT \'街道id\', ADD subdistrict_id BIGINT NOT NULL COMMENT \'社区id\'');
        $this->addSql('CREATE INDEX idx_66c5819c17fbd9b018f167a7dfd85ba9 ON orders_normal_orders (order_status, pay_type, is_profitsharing, profitsharing_status, order_auto_close_aftersales_time)');
        $this->addSql('ALTER TABLE promotions_limit ADD limit_type VARCHAR(50) DEFAULT NULL COMMENT \'限购类型, 全局限购：global, 店铺限购：shop\', ADD total_item_num INT DEFAULT 0 NOT NULL COMMENT \'限购商品总数\', ADD valid_item_num INT DEFAULT 0 NOT NULL COMMENT \'已导入的限购商品数\', ADD error_desc LONGTEXT DEFAULT NULL COMMENT \'导入错误描述\'');
        $this->addSql('ALTER TABLE promotions_limit_item DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE promotions_limit_item ADD distributor_id BIGINT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'店铺id\', ADD limit_num BIGINT NOT NULL COMMENT \'限购数量\'');
        $this->addSql('CREATE INDEX idx_distributor_id ON promotions_limit_item (distributor_id)');
        $this->addSql('CREATE INDEX idx_unique_item ON promotions_limit_item (company_id, distributor_id, item_id)');
        $this->addSql('ALTER TABLE promotions_limit_item ADD PRIMARY KEY (limit_id, distributor_id, item_id)');
        $this->addSql('ALTER TABLE promotions_limit_person ADD distributor_id BIGINT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'店铺id\'');
        $this->addSql('CREATE INDEX idx_distributor_user ON promotions_limit_person (distributor_id, user_id)');
        $this->addSql('ALTER TABLE shop_menu DROP menu_type');
        $this->addSql('ALTER TABLE trade ADD trade_no VARCHAR(255) DEFAULT \'0\' NOT NULL COMMENT \'每日交易序号\'');
        $this->addSql('CREATE INDEX idx_pagename_version_name_companyid_templatename ON wechat_weapp_setting (page_name(50), version(50), name(50), company_id, template_name(50))');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
