<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20260515163622 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE employee_purchase_activity_enterprise_behavior_log (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'主键\', company_id BIGINT NOT NULL COMMENT \'公司ID\', activity_id BIGINT NOT NULL COMMENT \'活动ID\', enterprise_id BIGINT NOT NULL COMMENT \'企业ID\', user_id BIGINT DEFAULT NULL COMMENT \'会员用户ID\', behavior_type VARCHAR(32) NOT NULL COMMENT \'行为类型\', result_status VARCHAR(16) DEFAULT NULL COMMENT \'行为结果，仅口令验证: success|fail\', visitor_key VARCHAR(64) DEFAULT NULL COMMENT \'未登录去重键\', ref_id BIGINT DEFAULT NULL COMMENT \'关联业务ID\', extra JSON DEFAULT NULL COMMENT \'扩展(DC2Type:json_array)\', created INT NOT NULL COMMENT \'创建时间戳\', INDEX idx_ep_aebl_company_activity (company_id, activity_id), INDEX idx_ep_aebl_act_ent_type (activity_id, enterprise_id, behavior_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'内购活动企业行为流水\' ');
        $this->addSql('CREATE TABLE employee_purchase_activity_enterprise_participate_user (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'主键\', company_id BIGINT NOT NULL COMMENT \'公司ID\', activity_id BIGINT NOT NULL COMMENT \'活动ID\', enterprise_id BIGINT NOT NULL COMMENT \'企业ID\', user_id BIGINT NOT NULL COMMENT \'会员用户ID\', created INT NOT NULL COMMENT \'创建时间戳\', INDEX idx_ep_aepu_activity_ent (activity_id, enterprise_id), UNIQUE INDEX uk_ep_aepu_scope_user (company_id, activity_id, enterprise_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'内购活动企业参与名额已占用用户\' ');
        $this->addSql('CREATE TABLE employee_purchase_activity_passphrase_enterprises (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'主键\', company_id BIGINT NOT NULL COMMENT \'公司ID\', activity_id BIGINT NOT NULL COMMENT \'活动ID\', enterprise_id BIGINT NOT NULL COMMENT \'企业ID\', participate_quota INT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'可参与名额\', passphrase_limitfee INT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'口令通道额度(分)，按企业\', passphrase_code VARCHAR(64) NOT NULL COMMENT \'口令编码\', created INT NOT NULL, updated INT DEFAULT NULL, INDEX idx_ep_ape_company_activity (company_id, activity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'内购活动口令绑定企业\' ');
        $this->addSql('CREATE TABLE employee_purchase_store_home_page (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, company_id BIGINT NOT NULL COMMENT \'公司ID\', distributor_id INT DEFAULT 0 NOT NULL COMMENT \'门店ID\', template_name VARCHAR(64) DEFAULT NULL COMMENT \'小程序模板名称\', page_name VARCHAR(255) NOT NULL COMMENT \'页面名称\', page_description VARCHAR(500) NOT NULL COMMENT \'页面描述\', page_share_title VARCHAR(255) DEFAULT NULL COMMENT \'分享标题\', page_share_desc VARCHAR(500) DEFAULT NULL COMMENT \'分享描述\', page_share_imageUrl VARCHAR(500) DEFAULT NULL COMMENT \'分享图片\', is_open INT DEFAULT 1 NOT NULL COMMENT \'是否开启\', weapp_customize_page_id BIGINT UNSIGNED DEFAULT NULL, created INT DEFAULT NULL, updated INT DEFAULT NULL, INDEX idx_company_id (company_id), INDEX idx_distributor_id (distributor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'内购模版（门店首页配置）\' ');
        $this->addSql('ALTER TABLE employee_purchase_activities ADD is_passphrase_enabled TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'是否开启口令通道\'');
        $this->addSql('ALTER TABLE employee_purchase_enterprises CHANGE auth_type auth_type VARCHAR(20) NOT NULL COMMENT \'登录类型,mobile:手机号,account:账号,email:邮箱,qr_code:二维码,no_verify:无需验证\'');
        $this->addSql('ALTER TABLE employee_purchase_orders_rel_activity ADD participate_quota_order_consumed TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'创单是否因本单扣减口令参与名额\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}