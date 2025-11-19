<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000008 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE distributor_wechat_rel (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'微信用户关联表自增id\', company_id BIGINT NOT NULL COMMENT \'公司id\', app_id VARCHAR(255) DEFAULT \'\' COMMENT \'微信app_id\', app_type VARCHAR(255) DEFAULT \'wx\' NOT NULL COMMENT \'类型：wx[公众号],wxa[小程序]\', openid VARCHAR(255) DEFAULT \'\' COMMENT \'微信openid\', unionid VARCHAR(255) DEFAULT \'\' NOT NULL COMMENT \'微信unionid\', operator_id BIGINT DEFAULT 0 COMMENT \'系统账户id\', bound_time BIGINT DEFAULT 0 COMMENT \'绑定时间\', INDEX idx_company_id (company_id), INDEX idx_operator_id (operator_id), UNIQUE INDEX ix_company_operator (company_id, app_type, operator_id), UNIQUE INDEX ix_company_wx_user (company_id, app_id, openid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'店务端微信关联表\' ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
