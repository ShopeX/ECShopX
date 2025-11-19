<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use AliyunsmsBundle\Entities\Scene;

class Version00000000000028 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE merchant CHANGE card_id_mask card_id_mask VARCHAR(255) DEFAULT NULL COMMENT \'结算银行卡号\'');
        $this->addSql('ALTER TABLE companys_operator_logs ADD updated INT DEFAULT NULL');
        $this->addSql('ALTER TABLE companys_profit_log ADD updated INT NOT NULL');
        $this->addSql('ALTER TABLE companys_protocol_update_log ADD updated INT DEFAULT NULL');
        $this->addSql('ALTER TABLE coupon_give_error_log ADD updated INT DEFAULT NULL');
        $this->addSql('ALTER TABLE coupon_give_log ADD updated INT DEFAULT NULL');
        $this->addSql('ALTER TABLE custom_declare_order_result ADD update_time INT DEFAULT NULL COMMENT \'更新时间\'');
        $this->addSql('ALTER TABLE members_article_fav ADD updated INT DEFAULT NULL COMMENT \'更新时间\', CHANGE created created bigint NOT NULL');
        $this->addSql('ALTER TABLE members_delete_record ADD updated INT DEFAULT NULL COMMENT \'更新时间\'');
        $this->addSql('ALTER TABLE members_distribution_fav ADD updated INT DEFAULT NULL COMMENT \'更新时间\', CHANGE created created bigint NOT NULL');
        $this->addSql('ALTER TABLE members_operate_log ADD updated INT DEFAULT NULL COMMENT \'更新时间\', CHANGE created created bigint NOT NULL');
        $this->addSql('ALTER TABLE members_protocol_log ADD updated INT DEFAULT NULL COMMENT \'更新时间\', CHANGE created created bigint NOT NULL');
        $this->addSql('ALTER TABLE orders_operate_logs ADD updated INT DEFAULT NULL COMMENT \'更新时间\'');
        $this->addSql('ALTER TABLE orders_rights_log ADD updated INT DEFAULT NULL COMMENT \'更新时间\'');
        $this->addSql('ALTER TABLE orders_rights_transfer_logs ADD updated INT DEFAULT NULL COMMENT \'更新时间\'');
        $this->addSql('ALTER TABLE popularize_brokerage ADD updated INT DEFAULT NULL');
        $this->addSql('ALTER TABLE popularize_promoter ADD updated INT DEFAULT NULL');
        $this->addSql('ALTER TABLE popularize_promoter_goods ADD updated INT DEFAULT NULL');
        $this->addSql('ALTER TABLE promotion_check_in ADD updated INT DEFAULT NULL');
        $this->addSql('ALTER TABLE promotions_notice_template ADD updated INT DEFAULT NULL');
        $this->addSql('ALTER TABLE promotions_scd_rel_user ADD updated INT DEFAULT NULL');
        $this->addSql('ALTER TABLE promotions_specific_crowd_discount ADD updated INT DEFAULT NULL');
        $this->addSql('ALTER TABLE promotions_turntable_log ADD updated INT DEFAULT NULL');
        $this->addSql('ALTER TABLE shop_menu_rel_type ADD updated INT DEFAULT NULL COMMENT \'更新时间\'');
        $this->addSql('ALTER TABLE sms_idiograph ADD updated INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sms_template ADD updated INT DEFAULT NULL');
        $this->addSql('ALTER TABLE trade_rate_reply ADD updated INT DEFAULT NULL COMMENT \'更新时间\'');
        $this->addSql('ALTER TABLE work_wechat_rel_logs ADD updated INT DEFAULT NULL COMMENT \'更新时间\'');
        $this->addSql('ALTER TABLE work_wechat_verify_domain_file ADD updated INT DEFAULT NULL COMMENT \'更新时间\'');

        $scenes = '[{"scene_title":"merchant_audit_success_notice","scene_name":"商户入驻成功通知","template_type":"1","default_template":"您申请的商户入驻已审批通过，请登录${商户入驻链接（H5）}查看商户登录的账号和密码。","variables":[{"var_name":"merchant_apply_url","var_title":"商户入驻链接（H5）"}]},{"scene_title":"merchant_audit_fail_notice","scene_name":"商户入驻审批未通过通知","template_type":"1","default_template":"您提交的商户入驻审批未通过，请及时登录${商户入驻链接（H5）}查看。","variables":[{"var_name":"merchant_apply_url","var_title":"商户入驻链接（H5）"}]},{"scene_title":"merchant_enter_success_notice","scene_name":"后台添加商户成功通知","template_type":"1","default_template":"商户入驻成功，请使用${商户网址}，登录账号为${手机号}，密码为${随机密码}登录商户后台。","variables":[{"var_name":"merchant_address","var_title":"商户网址"},{"var_name":"password","var_title":"随机密码"},{"var_name":"phone","var_title":"手机号"}]}]';
        $scenes = json_decode($scenes, true);
        $repository = app('registry')->getManager('default')->getRepository(Scene::class);
        $companys = $repository->lists(['scene_title' => 'merchant_audit_success_notice'], ['company_id'], 0);
        $repository->deleteBy(['scene_title' => ['merchant_audit_success_notice', 'merchant_enter_success_notice']]);
        foreach ($companys['list'] as $row) {
            foreach ($scenes as $item) {
                $tmp = [
                    'company_id' => $row['company_id'],
                    'scene_name' => $item['scene_name'],
                    'scene_title' => $item['scene_title'],
                    'template_type' => $item['template_type'],
                    'default_template' => $item['default_template'],
                    'variables' => json_encode($item['variables']),
                ];
                $repository->create($tmp);
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
