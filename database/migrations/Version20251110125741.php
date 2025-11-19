<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20251110125741 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $conn = $this->connection;
        
        // 创建 item_multi_lang_mod_lang_enCN 表
        $tableExists = $conn->fetchOne(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'item_multi_lang_mod_lang_enCN'"
        );
        
        if (!$tableExists) {
            $conn->executeStatement("
                CREATE TABLE `item_multi_lang_mod_lang_enCN` (
                    `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
                    `company_id` bigint(20) NOT NULL COMMENT '公司id',
                    `data_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '业务id字段',
                    `table_name` varchar(255) NOT NULL DEFAULT '' COMMENT '表名',
                    `field` varchar(255) NOT NULL DEFAULT '' COMMENT 'field,多语言对应字段',
                    `module_name` varchar(255) NOT NULL DEFAULT '' COMMENT 'module_name,模块名',
                    `lang` varchar(255) NOT NULL DEFAULT '' COMMENT '语言',
                    `attribute_value` text NOT NULL COMMENT '多语言值',
                    `created` int(11) NOT NULL,
                    `updated` int(11) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `ix_company_id` (`company_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='多语言字典库'
            ");
        }
        // 创建 item_multi_lang_mod_lang_zhCN 表
        $tableExists = $conn->fetchOne(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'item_multi_lang_mod_lang_zhCN'"
        );
        
        if (!$tableExists) {
            $conn->executeStatement("
                CREATE TABLE `item_multi_lang_mod_lang_zhCN` (
                    `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
                    `company_id` bigint(20) NOT NULL COMMENT '公司id',
                    `data_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '业务id字段',
                    `table_name` varchar(255) NOT NULL DEFAULT '' COMMENT '表名',
                    `field` varchar(255) NOT NULL DEFAULT '' COMMENT 'field,多语言对应字段',
                    `module_name` varchar(255) NOT NULL DEFAULT '' COMMENT 'module_name,模块名',
                    `lang` varchar(255) NOT NULL DEFAULT '' COMMENT '语言',
                    `attribute_value` text NOT NULL COMMENT '多语言值',
                    `created` int(11) NOT NULL,
                    `updated` int(11) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `ix_company_id` (`company_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='多语言字典库'
            ");
        }

        // outside_item_multi_lang_mod_lang_enCN
        $tableExists = $conn->fetchOne(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'outside_item_multi_lang_mod_lang_enCN'"
        );
        
        if (!$tableExists) {
            $conn->executeStatement("
                CREATE TABLE `outside_item_multi_lang_mod_lang_enCN` (
                    `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
                    `company_id` bigint(20) NOT NULL COMMENT '公司id',
                    `data_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '业务id字段',
                    `table_name` varchar(255) NOT NULL DEFAULT '' COMMENT '表名',
                    `field` varchar(255) NOT NULL DEFAULT '' COMMENT 'field,多语言对应字段',
                    `module_name` varchar(255) NOT NULL DEFAULT '' COMMENT 'module_name,模块名',
                    `lang` varchar(255) NOT NULL DEFAULT '' COMMENT '语言',
                    `attribute_value` text NOT NULL COMMENT '多语言值',
                    `created` int(11) NOT NULL,
                    `updated` int(11) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `ix_company_id` (`company_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='多语言字典库'
            ");
        }

        //outside_item_multi_lang_mod_lang_zhCN
        $tableExists = $conn->fetchOne(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'outside_item_multi_lang_mod_lang_zhCN'"
        );
        
        if (!$tableExists) {
            $conn->executeStatement("
                CREATE TABLE `outside_item_multi_lang_mod_lang_zhCN` (
                    `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
                    `company_id` bigint(20) NOT NULL COMMENT '公司id',
                    `data_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '业务id字段',
                    `table_name` varchar(255) NOT NULL DEFAULT '' COMMENT '表名',
                    `field` varchar(255) NOT NULL DEFAULT '' COMMENT 'field,多语言对应字段',
                    `module_name` varchar(255) NOT NULL DEFAULT '' COMMENT 'module_name,模块名',
                    `lang` varchar(255) NOT NULL DEFAULT '' COMMENT '语言',
                    `attribute_value` text NOT NULL COMMENT '多语言值',
                    `created` int(11) NOT NULL,
                    `updated` int(11) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `ix_company_id` (`company_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='多语言字典库'
            ");
        }

        // multi_lang_mod基表
        $tableExists = $conn->fetchOne(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'multi_lang_mod'"
        );
        
        if (!$tableExists) {
            $conn->executeStatement("
                CREATE TABLE `multi_lang_mod` (
                    `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
                    `company_id` bigint(20) NOT NULL COMMENT '公司id',
                    `data_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '业务id字段',
                    `table_name` varchar(255) NOT NULL DEFAULT '' COMMENT '表名',
                    `field` varchar(255) NOT NULL DEFAULT '' COMMENT 'field,多语言对应字段',
                    `module_name` varchar(255) NOT NULL DEFAULT '' COMMENT 'module_name,模块名',
                    `lang` varchar(255) NOT NULL DEFAULT '' COMMENT '语言',
                    `attribute_value` text NOT NULL COMMENT '多语言值',
                    `created` int(11) NOT NULL,
                    `updated` int(11) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `ix_company_id` (`company_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='多语言字典库'
            ");
        }

        // multi_lang_mod基表
        $tableExists = $conn->fetchOne(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'common_lang_mod_cn'"
        );
        
        if (!$tableExists) {
            $conn->executeStatement("
                CREATE TABLE `common_lang_mod_cn` (
                    `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
                    `company_id` bigint(20) NOT NULL COMMENT '公司id',
                    `data_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '业务id字段',
                    `table_name` varchar(255) NOT NULL DEFAULT '' COMMENT '表名',
                    `field` varchar(255) NOT NULL DEFAULT '' COMMENT 'field,多语言对应字段',
                    `module_name` varchar(255) NOT NULL DEFAULT '' COMMENT 'module_name,模块名',
                    `lang` varchar(255) NOT NULL DEFAULT '' COMMENT '语言',
                    `attribute_value` text NOT NULL COMMENT '多语言值',
                    `created` int(11) NOT NULL,
                    `updated` int(11) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `ix_company_id` (`company_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='多语言字典库'
            ");
        }

        // multi_lang_mod基表
        $tableExists = $conn->fetchOne(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'common_lang_mod_en'"
        );
        
        if (!$tableExists) {
            $conn->executeStatement("
                CREATE TABLE `common_lang_mod_en` (
                    `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
                    `company_id` bigint(20) NOT NULL COMMENT '公司id',
                    `data_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '业务id字段',
                    `table_name` varchar(255) NOT NULL DEFAULT '' COMMENT '表名',
                    `field` varchar(255) NOT NULL DEFAULT '' COMMENT 'field,多语言对应字段',
                    `module_name` varchar(255) NOT NULL DEFAULT '' COMMENT 'module_name,模块名',
                    `lang` varchar(255) NOT NULL DEFAULT '' COMMENT '语言',
                    `attribute_value` text NOT NULL COMMENT '多语言值',
                    `created` int(11) NOT NULL,
                    `updated` int(11) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `ix_company_id` (`company_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='多语言字典库'
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
        $conn->executeStatement("DROP TABLE IF EXISTS `item_multi_lang_mod_lang_enCN`");
        $conn->executeStatement("DROP TABLE IF EXISTS `item_multi_lang_mod_lang_zhCN`");
        $conn->executeStatement("DROP TABLE IF EXISTS `outside_item_multi_lang_mod_lang_enCN`");
        $conn->executeStatement("DROP TABLE IF EXISTS `outside_item_multi_lang_mod_lang_zhCN`");
        $conn->executeStatement("DROP TABLE IF EXISTS `multi_lang_mod`");
    }
}
