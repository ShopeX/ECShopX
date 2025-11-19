<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use AftersalesBundle\Services\AftersalesRefundService;

class Version00000000000026 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ali_mini_app_setting (setting_id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'配置id\', company_id BIGINT NOT NULL COMMENT \'公司id\', authorizer_appid VARCHAR(64) NOT NULL COMMENT \'支付宝小程序appid\', merchant_private_key LONGTEXT NOT NULL COMMENT \'应用私钥\', api_sign_method VARCHAR(64) NOT NULL COMMENT \'api加密类型\', alipay_cert_path LONGTEXT DEFAULT NULL COMMENT \'支付宝公钥证书文件路径\', alipay_root_cert_path LONGTEXT DEFAULT NULL COMMENT \'支付宝根证书文件路径\', merchant_cert_path LONGTEXT DEFAULT NULL COMMENT \'应用公钥证书文件路径\', alipay_public_key LONGTEXT DEFAULT NULL COMMENT \'支付宝公钥字符串\', notify_url VARCHAR(255) DEFAULT NULL COMMENT \'支付类接口异步通知接收服务地址\', encrypt_key VARCHAR(255) DEFAULT NULL COMMENT \'AES密钥\', created bigint NOT NULL, updated bigint NOT NULL, UNIQUE INDEX ix_company_id (company_id), UNIQUE INDEX ix_authorizer_appid (authorizer_appid), PRIMARY KEY(setting_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'支付宝小程序配置表\' ');
        $this->addSql('ALTER TABLE members ADD alipay_appid VARCHAR(64) DEFAULT NULL COMMENT \'支付宝小程序appid\'');
        $this->addSql('ALTER TABLE members_subscribe_notice ADD source VARCHAR(10) DEFAULT \'wechat\' NOT NULL COMMENT \'订阅来源 wechat:微信 alipay:支付宝\'');
        $this->addSql('ALTER TABLE aftersales ADD contact VARCHAR(500) DEFAULT NULL COMMENT \'联系人\', ADD return_type VARCHAR(20) DEFAULT \'logistics\' NOT NULL COMMENT \'退货方式：logistics寄回 offline到店退\', ADD return_distributor_id BIGINT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'退货门店ID\'');
        $this->addSql('ALTER TABLE distribution_distributor ADD offline_aftersales INT DEFAULT 0 NOT NULL COMMENT \'本店订单到店售后\', ADD offline_aftersales_self INT DEFAULT 0 NOT NULL COMMENT \'退货到本店退货点\', ADD offline_aftersales_distributor_id VARCHAR(255) DEFAULT NULL COMMENT \'本店订单到其他店铺售后\', ADD offline_aftersales_other INT DEFAULT 0 NOT NULL COMMENT \'其他店铺订单到本店售后\'');
        $this->addSql('ALTER TABLE distributor_aftersales_address ADD lng VARCHAR(255) DEFAULT NULL COMMENT \'纬度\', ADD lat VARCHAR(255) DEFAULT NULL COMMENT \'经度\', ADD name VARCHAR(255) DEFAULT NULL COMMENT \'退货点名称\', ADD hours VARCHAR(50) DEFAULT NULL COMMENT \'营业时间\', ADD return_type VARCHAR(20) DEFAULT \'logistics\' NOT NULL COMMENT \'退货方式：logistics寄回 offline到店退\', CHANGE contact contact VARCHAR(500) DEFAULT NULL COMMENT \'联系人\'');
        $this->addSql('ALTER TABLE pages_open_screen_ad ADD show_time VARCHAR(255) DEFAULT \'first\' NOT NULL COMMENT \'曝光设置：first,always\'');

        $offset = 0;
        $limit = 100;
        $aftersalesRefundService = new AftersalesRefundService();
        do {
            $refund = $aftersalesRefundService->getList(['refund_status' => 'SUCCESS'], $offset, $limit);
            $offset += $limit;
            foreach ($refund['list'] as $row) {
                $aftersalesRefundService->updateRefundedFee($row);
            }
        } while($offset < $refund['total_count']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
