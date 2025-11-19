<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000016 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE statement_details (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'ID\', company_id BIGINT NOT NULL COMMENT \'公司id\', merchant_id BIGINT NOT NULL COMMENT \'商户id\', distributor_id BIGINT NOT NULL COMMENT \'店铺id\', statement_id BIGINT NOT NULL COMMENT \'结算单ID\', statement_no VARCHAR(20) NOT NULL COMMENT \'结算单号\', order_id BIGINT NOT NULL COMMENT \'订单号\', total_fee INT NOT NULL COMMENT \'实付金额，以分为单位\', freight_fee INT NOT NULL COMMENT \'运费金额，以分为单位\', intra_city_freight_fee INT NOT NULL COMMENT \'同城配金额，以分为单位\', rebate_fee INT NOT NULL COMMENT \'分销佣金，以分为单位\', refund_fee INT NOT NULL COMMENT \'退款金额，以分为单位\', statement_fee INT NOT NULL COMMENT \'结算金额，以分为单位\', pay_type VARCHAR(255) NOT NULL COMMENT \'支付方式\', created INT NOT NULL, updated INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'结算单明细\' ');
        $this->addSql('CREATE TABLE statement_period_setting (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'ID\', company_id BIGINT NOT NULL COMMENT \'公司id\', merchant_id BIGINT NOT NULL COMMENT \'商户id\', distributor_id BIGINT NOT NULL COMMENT \'店铺id\', period VARCHAR(255) NOT NULL COMMENT \'结算周期 day:天 week:周 month:月\', created INT NOT NULL, updated INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'结算周期设置\' ');
        $this->addSql('CREATE TABLE statements (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'ID\', company_id BIGINT NOT NULL COMMENT \'公司id\', merchant_id BIGINT NOT NULL COMMENT \'商户id\', distributor_id BIGINT NOT NULL COMMENT \'店铺id\', statement_no VARCHAR(20) NOT NULL COMMENT \'结算单号\', order_num INT UNSIGNED NOT NULL COMMENT \'订单数量\', total_fee INT NOT NULL COMMENT \'实付金额，以分为单位\', freight_fee INT NOT NULL COMMENT \'运费金额，以分为单位\', intra_city_freight_fee INT NOT NULL COMMENT \'同城配金额，以分为单位\', rebate_fee INT NOT NULL COMMENT \'分销佣金，以分为单位\', refund_fee INT NOT NULL COMMENT \'退款金额，以分为单位\', statement_fee INT NOT NULL COMMENT \'结算金额，以分为单位\', start_time INT NOT NULL COMMENT \'结算周期开始时间\', end_time INT NOT NULL COMMENT \'结算周期结束时间\', confirm_time INT DEFAULT NULL COMMENT \'确认时间\', statement_time INT DEFAULT NULL COMMENT \'结算时间\', statement_status VARCHAR(255) DEFAULT \'ready\' NOT NULL COMMENT \'结算状态 ready:待商家确认 confirmed待平台结算 done:已结算\', created INT NOT NULL, updated INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'结算单\' ');
        $this->addSql('ALTER TABLE promotions_limit ADD source_type VARCHAR(20) DEFAULT NULL COMMENT \'添加者类型：distributor\', ADD source_id BIGINT DEFAULT 0 COMMENT \'添加者ID: 如店铺ID\'');
        $this->addSql('ALTER TABLE promotions_package ADD source_type VARCHAR(20) DEFAULT NULL COMMENT \'添加者类型：distributor\', ADD source_id BIGINT DEFAULT 0 COMMENT \'添加者ID: 如店铺ID\'');
        $this->addSql('ALTER TABLE trade ADD is_settled TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'是否分账\'');
        $this->addSql('UPDATE wechat_authorization SET miniprograminfo=null where miniprograminfo=""');
        $this->addSql('ALTER TABLE wechat_authorization CHANGE authorizer_appid authorizer_appid VARCHAR(64) NOT NULL COMMENT \'(公众号，小程序)微信appid\', CHANGE authorizer_appsecret authorizer_appsecret VARCHAR(255) DEFAULT NULL COMMENT \'(公众号，小程序)微信appsecret\', CHANGE authorizer_refresh_token authorizer_refresh_token VARCHAR(255) DEFAULT NULL COMMENT \'(公众号，小程序)微信refresh_token\', CHANGE nick_name nick_name VARCHAR(50) NOT NULL COMMENT \'(公众号，小程序)昵称\', CHANGE head_img head_img VARCHAR(255) DEFAULT NULL COMMENT \'(公众号，小程序)头像\', CHANGE service_type_info service_type_info INT DEFAULT NULL COMMENT \'(公众号，小程序)类型。可选值有 0代表订阅号；1代表由历史老帐号升级后的订阅号；2代表服务号；3代表小程序(自定义)\', CHANGE verify_type_info verify_type_info INT DEFAULT NULL COMMENT \'(公众号，小程序)认证类型。-1代表未认证;0代表微信认证;1代表新浪微博认证;2代表腾讯微博认证;3代表已资质认证通过但还未通过名称认证;4代表已资质认证通过、还未通过名称认证，但通过了新浪微博认证;5代表已资质认证通过、还未通过名称认证，但通过了腾讯微博认证\', CHANGE user_name user_name VARCHAR(32) DEFAULT NULL COMMENT \'(公众号，小程序)原始 ID\', CHANGE signature signature LONGTEXT DEFAULT NULL COMMENT \'(小程序)账号介绍\', CHANGE principal_name principal_name VARCHAR(255) DEFAULT NULL COMMENT \'(公众号，小程序)主体名称\', CHANGE alias alias VARCHAR(50) DEFAULT NULL COMMENT \'(公众号)授权方公众号所设置的微信号，可能为空\', CHANGE business_info business_info JSON DEFAULT NULL COMMENT \'(公众号，小程序)用以了解以下功能的开通状况（0代表未开通，1代表已开通）。open_store:是否开通微信门店功能;open_scan:是否开通微信扫商品功能;open_pay:是否开通微信支付功能;open_card:是否开通微信卡券功能;open_shake:是否开通微信摇一摇功能(DC2Type:json_array)\', CHANGE qrcode_url qrcode_url VARCHAR(255) DEFAULT NULL COMMENT \'(公众号，小程序)二维码图片的URL\', CHANGE miniprograminfo miniprograminfo JSON DEFAULT NULL COMMENT \'(小程序)小程序配置，根据这个字段判断是否为小程序类型授权(DC2Type:json_array)\', CHANGE func_info func_info VARCHAR(255) DEFAULT NULL COMMENT \'(公众号，小程序)授权给开发者的权限集列表,逗号隔开\', CHANGE auto_publish auto_publish SMALLINT DEFAULT 0 NOT NULL COMMENT \'(小程序)自动发布,第三方授权模式才有用，直连用不到此配置。1:自动发布,0:不自动发布\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
