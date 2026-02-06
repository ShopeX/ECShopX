<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20251217100000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // 会员标签组
        $this->addSql("CREATE TABLE members_tag_groups (
            group_id BIGINT AUTO_INCREMENT NOT NULL COMMENT '标签组ID',
            company_id BIGINT NOT NULL COMMENT '公司id',
            group_name VARCHAR(100) NOT NULL COMMENT '标签组名称',
            description VARCHAR(255) DEFAULT NULL COMMENT '描述',
            distributor_id BIGINT UNSIGNED DEFAULT 0 NOT NULL COMMENT '分销商id',
            created BIGINT NOT NULL,
            updated BIGINT NOT NULL,
            INDEX idx_company_id (company_id),
            INDEX idx_distributor_id (distributor_id),
            PRIMARY KEY(group_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '会员标签组'");

        // 标签组与标签关联
        $this->addSql("CREATE TABLE members_tag_group_rel (
            id BIGINT AUTO_INCREMENT NOT NULL COMMENT '主键ID',
            group_id BIGINT NOT NULL COMMENT '标签组ID',
            tag_id BIGINT NOT NULL COMMENT '标签ID',
            company_id BIGINT NOT NULL COMMENT '公司ID',
            distributor_id BIGINT UNSIGNED DEFAULT 0 NOT NULL COMMENT '分销商id',
            created BIGINT NOT NULL,
            INDEX idx_group_id (group_id),
            INDEX idx_tag_id (tag_id),
            INDEX idx_company_distributor (company_id, distributor_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '会员标签组与标签关系表'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE IF EXISTS members_tag_group_rel');
        $this->addSql('DROP TABLE IF EXISTS members_tag_groups');
    }
}
