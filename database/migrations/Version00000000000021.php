<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000021 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE companys_operator_cart (cart_id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'购物车ID\', company_id BIGINT NOT NULL COMMENT \'企业id\', distributor_id BIGINT DEFAULT 0 NOT NULL COMMENT \'店铺id\', operator_id BIGINT NOT NULL COMMENT \'管理员id\', item_id BIGINT NOT NULL COMMENT \'商品id\', num BIGINT DEFAULT 1 NOT NULL COMMENT \'商品数量\', is_checked TINYINT(1) DEFAULT \'1\' NOT NULL COMMENT \'购物车是否选中\', special_type VARCHAR(255) DEFAULT \'normal\' NOT NULL COMMENT \'商品特殊类型 drug 处方药 normal 普通商品\', INDEX idx_item_id (item_id), INDEX idx_operator_id (operator_id), INDEX idx_company_id (company_id), PRIMARY KEY(cart_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'导购员购物车\' ');
        $this->addSql('CREATE TABLE companys_operator_pending_order (pending_id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'挂单ID\', company_id BIGINT NOT NULL COMMENT \'企业id\', distributor_id BIGINT DEFAULT 0 NOT NULL COMMENT \'店铺id\', operator_id BIGINT NOT NULL COMMENT \'管理员id\', user_id BIGINT DEFAULT 0 NOT NULL COMMENT \'用户id\', pending_type VARCHAR(255) DEFAULT \'cart\' NOT NULL COMMENT \'挂起类型 cart:收银台 order:订单\', pending_data LONGTEXT NOT NULL COMMENT \'暂存数据\', created INT NOT NULL, updated INT DEFAULT NULL, INDEX idx_operator_id (operator_id), INDEX idx_company_id (company_id), PRIMARY KEY(pending_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'导购挂单\' ');
        
        $this->addSql('ALTER TABLE members_wechatusers ADD nickname VARCHAR(500) DEFAULT NULL COMMENT \'昵称\', ADD headimgurl VARCHAR(255) DEFAULT NULL COMMENT \'头像url\', CHANGE authorizer_appid authorizer_appid VARCHAR(64) NOT NULL COMMENT \'小程序或者公众号appid\', CHANGE need_transfer need_transfer TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'是否需要迁移。0:不用迁移或迁移完成；1:需要迁移\', CHANGE created created bigint NOT NULL, CHANGE updated updated bigint NOT NULL');
        $this->addSql('UPDATE members_wechatusers AS a, members_wechatusers_info AS b SET a.nickname=b.nickname,a.headimgurl=b.headimgurl WHERE a.unionid=b.unionid AND a.company_id=b.company_id');
        $this->addSql('DROP TABLE members_wechatusers_info');
        $this->addSql('ALTER TABLE orders_normal_orders ADD operator_id INT DEFAULT 0 COMMENT \'操作者id\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
