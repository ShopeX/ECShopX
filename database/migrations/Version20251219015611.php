<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20251219015611 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $conn = $this->connection;
        
        // 创建 member_segment_rules 表
        $tableExists = $conn->fetchOne(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'member_segment_rules'"
        );
        
        if (!$tableExists) {
            $conn->executeStatement("
                CREATE TABLE `member_segment_rules` (
                    `rule_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '规则id',
                    `company_id` bigint(20) NOT NULL COMMENT '公司id',
                    `distributor_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '分销商id',
                    `rule_name` varchar(100) NOT NULL COMMENT '规则名称（分群标签名称）',
                    `description` text COMMENT '人群说明',
                    `rule_config` text NOT NULL COMMENT '规则配置（层级结构，JSON格式存储）',
                    `tag_ids` text COMMENT '关联的标签ID数组（JSON格式）',
                    `status` smallint(6) NOT NULL DEFAULT '1' COMMENT '状态：0=禁用，1=启用',
                    `created` bigint(20) NOT NULL COMMENT '创建时间（时间戳）',
                    `updated` bigint(20) DEFAULT NULL COMMENT '更新时间（时间戳）',
                    PRIMARY KEY (`rule_id`),
                    KEY `idx_company_distributor` (`company_id`, `distributor_id`),
                    KEY `idx_status` (`status`),
                    KEY `idx_created` (`created`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='人群规则表'
            ");
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $conn = $this->connection;
        
        // 删除表
        $conn->executeStatement("DROP TABLE IF EXISTS `member_segment_rules`");
    }
}
