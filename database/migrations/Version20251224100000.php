<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20251224100000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $conn = $this->connection;
        
        // 为 member_tag_groups 表添加 wechat_group_id 字段
        $conn->executeStatement("
            ALTER TABLE `members_tag_groups` 
            ADD COLUMN `wechat_group_id` VARCHAR(100) NULL COMMENT '企业微信标签组ID' AFTER `distributor_id`,
            ADD INDEX `idx_wechat_group_id` (`wechat_group_id`)
        ");
        
        // 为 members_tags 表添加 wechat_tag_id 字段
        $conn->executeStatement("
            ALTER TABLE `members_tags` 
            ADD COLUMN `wechat_tag_id` VARCHAR(100) NULL COMMENT '企业微信标签ID' AFTER `source`,
            ADD INDEX `idx_wechat_tag_id` (`wechat_tag_id`)
        ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $conn = $this->connection;
        
        // 删除 member_tag_groups 表的 wechat_group_id 字段
        $conn->executeStatement("
            ALTER TABLE `member_tag_groups` 
            DROP INDEX `idx_wechat_group_id`,
            DROP COLUMN `wechat_group_id`
        ");
        
        // 删除 members_tags 表的 wechat_tag_id 字段
        $conn->executeStatement("
            ALTER TABLE `members_tags` 
            DROP INDEX `idx_wechat_tag_id`,
            DROP COLUMN `wechat_tag_id`
        ");
    }
}

