<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20250115143000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // 创建分类税率表 category_tax_rate
        $this->addSql('CREATE TABLE category_tax_rate (
            id BIGINT AUTO_INCREMENT NOT NULL COMMENT "自增id",
            company_id BIGINT NOT NULL COMMENT "公司id",
            sales_party_id VARCHAR(64) NOT NULL COMMENT "销售方ID",
            tax_rate_type VARCHAR(32) NOT NULL COMMENT "税率分类：ALL/SPECIFIED",
            category_ids LONGTEXT DEFAULT NULL COMMENT "分类ID数组，json存储",
            invoice_tax_rate VARCHAR(16) NOT NULL COMMENT "发票税率，如13%",
            created_at INT NOT NULL COMMENT "创建时间",
            updated_at INT DEFAULT NULL COMMENT "更新时间",
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = "分类税率表"');

        // 添加索引以提高查询性能
        $this->addSql('CREATE INDEX idx_company_id ON category_tax_rate (company_id)');
        $this->addSql('CREATE INDEX idx_sales_party_id ON category_tax_rate (sales_party_id)');
        $this->addSql('CREATE INDEX idx_tax_rate_type ON category_tax_rate (tax_rate_type)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // 删除分类税率表
        $this->addSql('DROP TABLE category_tax_rate');
    }
}