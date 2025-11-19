<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000030 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE community_activity ADD share_image_url LONGTEXT DEFAULT NULL COMMENT \'分享图片\', CHANGE activity_status activity_status VARCHAR(255) NOT NULL COMMENT \'活动状态 private私有 public公开 protected隐藏 success确认成团 fail成团失败\'');
        $this->addSql('ALTER TABLE operator_data_pass_log CHANGE url url VARCHAR(1000) DEFAULT \'\' NOT NULL COMMENT \'全地址\'');
        $this->addSql('ALTER TABLE promotions_bargain_log CHANGE nickname nickname VARCHAR(255) DEFAULT NULL COMMENT \'用户昵称\', CHANGE headimgurl headimgurl VARCHAR(255) DEFAULT NULL COMMENT \'用户头像url\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
