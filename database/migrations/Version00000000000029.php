<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000029 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE adapay_member ADD is_created TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'会员是否创建成功\'');
        $this->addSql('ALTER TABLE members_subscribe_notice ADD distributor_id INT DEFAULT 0 NOT NULL COMMENT \'店铺id\', CHANGE updated updated bigint NOT NULL, CHANGE created created bigint NOT NULL');
        $this->addSql('ALTER TABLE promotions_seckill_activity ADD disabled TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'是否失效\'');
        $this->addSql('ALTER TABLE promotions_seckill_rel_goods ADD disabled TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'是否失效\'');
        $this->addSql('ALTER TABLE shop_salesperson ADD work_clear_userid VARCHAR(255) DEFAULT NULL COMMENT \'企业微信userid[用于对接导购存储明文userid]\', CHANGE work_userid work_userid VARCHAR(255) DEFAULT NULL COMMENT \'企业微信userid[如果是内部应用则是明文，如果是第三方应用则是密文]\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
