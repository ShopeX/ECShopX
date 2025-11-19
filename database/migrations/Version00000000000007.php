<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000007 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE point_member_log CHANGE journal_type journal_type SMALLINT NOT NULL COMMENT \'积分交易类型，1:注册送积分 2.推荐送分 3.充值返积分 4.推广注册返积分 5.积分换购 6.储值兑换积分 7.订单返积分 8.会员等级返佣 9.取消订处理积分 10.售后处理积分 11.大转盘抽奖送积分 12:管理员手动调整积分 13.外部开发者同步进来的会员积分 14:会员信息导入，初始化积分 15:会员信息导入，更新会员调整积分\'');
        $this->addSql('ALTER TABLE register_promotions ADD register_jump_path VARCHAR(500) DEFAULT NULL COMMENT \'注册引导跳转路径\'');
        $this->addSql('ALTER TABLE shop_menu CHANGE version version SMALLINT DEFAULT 1 NOT NULL COMMENT \'菜单版本,1:平台菜单;2:IT端菜单,3:店铺菜单,4:供应商菜单,5:经销商菜单,6:商户菜单\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
