<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20260206194056 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE items_group (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'ID\', company_id BIGINT DEFAULT 0 NOT NULL COMMENT \'公司ID\', regionauth_id BIGINT DEFAULT 0 NOT NULL COMMENT \'地区ID\', group_key VARCHAR(50) DEFAULT \'\' NOT NULL COMMENT \'分组唯一码\', remark VARCHAR(100) DEFAULT \'\' NOT NULL COMMENT \'备注\', created INT NOT NULL, updated INT DEFAULT NULL, INDEX idx_group_key (group_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'商品分组\' ');
        $this->addSql('CREATE TABLE items_group_rel_item (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'ID\', company_id BIGINT DEFAULT 0 NOT NULL COMMENT \'公司ID\', regionauth_id BIGINT DEFAULT 0 NOT NULL COMMENT \'地区ID\', group_id BIGINT DEFAULT 0 NOT NULL COMMENT \'分组ID\', group_type VARCHAR(50) DEFAULT \'\' NOT NULL COMMENT \'分组类型(coupon, widget, marketing)\', item_id BIGINT DEFAULT 0 NOT NULL COMMENT \'sku-id\', goods_id BIGINT DEFAULT 0 NOT NULL COMMENT \'spu-id\', is_del BIGINT DEFAULT 0 NOT NULL COMMENT \'是否删除\', created INT NOT NULL, updated INT DEFAULT NULL, INDEX idx_goods_id (goods_id), INDEX idx_group_id (group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'商品分组关联商品\' ');
        $this->addSql('CREATE TABLE pages_ad_place (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'设置id\', company_id BIGINT NOT NULL COMMENT \'公司id\', regionauth_id BIGINT DEFAULT 0 NOT NULL COMMENT \'区域id\', use_bound INT DEFAULT 0 COMMENT \'适用范围: 0:全部,1:指定店铺\', ad_type VARCHAR(20) NOT NULL COMMENT \'广告类型：弹窗=>popup，轮播图=>carousel\', name VARCHAR(100) NOT NULL COMMENT \'广告位名称\', pages VARCHAR(30) NOT NULL COMMENT \'关联页面\', start_time BIGINT NOT NULL COMMENT \'开始时间\', end_time BIGINT NOT NULL COMMENT \'结束时间\', setting LONGTEXT DEFAULT NULL COMMENT \'设置\', auto_play INT DEFAULT 0 NOT NULL COMMENT \'自动播放\', play_interval INT DEFAULT 3 NOT NULL COMMENT \'播放间隔时间\', auto_close INT DEFAULT 0 NOT NULL COMMENT \'自动关闭\', close_delay INT DEFAULT 10 NOT NULL COMMENT \'关闭延迟时间\', source_id BIGINT DEFAULT 0 NOT NULL COMMENT \'添加者ID: 如店铺ID\', created INT NOT NULL, updated INT DEFAULT NULL, audit_status VARCHAR(255) DEFAULT \'submitting\' NOT NULL COMMENT \'审核状态 submitting待提交 processing审核中 approved成功 rejected审核拒绝\', audit_remark VARCHAR(255) DEFAULT NULL COMMENT \'审核备注\', sort INT DEFAULT 0 NOT NULL COMMENT \'排序\', tracking_code VARCHAR(100) DEFAULT NULL COMMENT \'埋点上报参数\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'广告位设置\' ');
        $this->addSql('CREATE TABLE pages_ad_place_rel_distributors (company_id BIGINT NOT NULL COMMENT \'公司id\', ad_place_id BIGINT NOT NULL COMMENT \'广告位id\', distributor_id BIGINT NOT NULL COMMENT \'店铺id\', PRIMARY KEY(company_id, ad_place_id, distributor_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'广告位与店铺关联表\' ');
        $this->addSql('CREATE TABLE pages_ad_place_rel_member_tags (company_id BIGINT NOT NULL COMMENT \'公司id\', ad_place_id BIGINT NOT NULL COMMENT \'广告位id\', tag_id BIGINT NOT NULL COMMENT \'人群标签id\', PRIMARY KEY(company_id, ad_place_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'广告位与人群标签关联表\' ');
        $this->addSql('CREATE TABLE pages_side_bar (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'设置id\', company_id BIGINT NOT NULL COMMENT \'公司id\', regionauth_id BIGINT DEFAULT 0 NOT NULL COMMENT \'区域id\', name VARCHAR(100) NOT NULL COMMENT \'名称\', pages VARCHAR(100) NOT NULL COMMENT \'关联页面\', disabled TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'是否禁用\', setting LONGTEXT DEFAULT NULL COMMENT \'设置\', created INT NOT NULL, updated INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'侧边栏设置\' ');
        $this->addSql('ALTER TABLE aftersales_detail CHANGE refunded_num refunded_num INT UNSIGNED DEFAULT 0 COMMENT \'实际入库数量\'');
        $this->addSql('ALTER TABLE aftersales_refund CHANGE freight freight INT UNSIGNED DEFAULT 0 COMMENT \'退款运费，根据freight_type存储现金（分）或积分值\'');
        $this->addSql('ALTER TABLE business_rep_user CHANGE create_time create_time bigint NOT NULL');
        $this->addSql('ALTER TABLE common_lang_mod_ar CHANGE data_id data_id BIGINT NOT NULL COMMENT \'业务id字段\'');
        $this->addSql('ALTER TABLE common_lang_mod_cn CHANGE data_id data_id BIGINT NOT NULL COMMENT \'业务id字段\'');
        $this->addSql('ALTER TABLE common_lang_mod_en CHANGE data_id data_id BIGINT NOT NULL COMMENT \'业务id字段\'');
        $this->addSql('ALTER TABLE community_activity CHANGE activity_status activity_status VARCHAR(255) NOT NULL COMMENT \'活动状态 private私有 public公开 protected隐藏 success确认成团 fail成团失败\'');
        $this->addSql('ALTER TABLE distribution_distributor ADD first_letter VARCHAR(5) DEFAULT \'\' NOT NULL COMMENT \'店铺名称首字母\'');
        $this->addSql('ALTER TABLE items_category ADD regionauth_id BIGINT DEFAULT 0 NOT NULL COMMENT \'地区ID\', CHANGE invoice_tax_rate_id invoice_tax_rate_id BIGINT DEFAULT NULL COMMENT \'发票税率ID\', CHANGE invoice_tax_rate invoice_tax_rate VARCHAR(16) DEFAULT NULL COMMENT \'发票税率\'');
        $this->addSql('ALTER TABLE kaquan_user_discount CHANGE card_id card_id BIGINT NOT NULL COMMENT \'微信用户领取的卡券 id \'');
        $this->addSql('ALTER TABLE kujiale_designer_works_rel_cities CHANGE id id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'id\', CHANGE design_id design_id VARCHAR(255) NOT NULL COMMENT \'方案id\'');
        $this->addSql('ALTER TABLE members CHANGE has_fp has_fp TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'是否有分配导购\', CHANGE is_become_friend is_become_friend TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'是否已加为好友。0:否；1:是\'');
        $this->addSql('ALTER TABLE members_subscribe_notice CHANGE updated updated bigint NOT NULL');
        $this->addSql('ALTER TABLE multi_lang_mod CHANGE data_id data_id BIGINT NOT NULL COMMENT \'业务id字段\'');
        $this->addSql('ALTER TABLE orders_normal_orders_items CHANGE is_invoice is_invoice INT DEFAULT 0 NOT NULL COMMENT \'是否开票,0否 1已开票 2开票中 3红冲\'');
        $this->addSql('ALTER TABLE pages_open_screen_ad ADD start_time BIGINT NOT NULL COMMENT \'开始时间\', ADD end_time BIGINT NOT NULL COMMENT \'结束时间\'');
        $this->addSql('ALTER TABLE pages_template ADD regionauth_id BIGINT DEFAULT 0 COMMENT \'区域id\', CHANGE weapp_pages weapp_pages VARCHAR(255) DEFAULT \'index\' COMMENT \'模版页面:index-首页 distributor_index-门店首页\', CHANGE lang lang VARCHAR(255) DEFAULT NULL COMMENT \'模版语言\'');
        $this->addSql('ALTER TABLE pages_template_set ADD regionauth_id BIGINT DEFAULT 0 COMMENT \'区域id\'');
        $this->addSql('ALTER TABLE popularize_promoter_identity CHANGE is_default is_default INT NOT NULL COMMENT \'是否为默认\'');
        $this->addSql('ALTER TABLE promotions_package_item CHANGE is_show is_show TINYINT(1) DEFAULT NULL COMMENT \'列表页是否显示\'');
        $this->addSql('ALTER TABLE resources CHANGE eid eid VARCHAR(255) DEFAULT NULL COMMENT \'企业id\', CHANGE passport_uid passport_uid VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE selfservice_registration_activity CHANGE pics pics TEXT');
        $this->addSql('ALTER TABLE user_signin_rules CHANGE days_required days_required  BIGINT NOT NULL COMMENT \'需要的天数\'');
        $this->addSql('ALTER TABLE wechat_weapp_customize_page ADD regionauth_id BIGINT DEFAULT 0 NOT NULL COMMENT \'区域id\', CHANGE page_type page_type VARCHAR(255) DEFAULT \'normal\' NOT NULL COMMENT \'页面类型 normal:普通页面 salesperson:导购首页 category:分类页 my:我的\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
