<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000047 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE supplier_items_attr (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'ID\', company_id BIGINT NOT NULL COMMENT \'公司ID\', item_id BIGINT NOT NULL COMMENT \'商品ID\', attribute_id BIGINT DEFAULT 0 NOT NULL COMMENT \'商品属性id\', is_del BIGINT DEFAULT 0 NOT NULL COMMENT \'是否需要删除\', attribute_type VARCHAR(15) NOT NULL COMMENT \'商品属性类型 unit 单位，brand 品牌，item_params 商品参数, item_spec 规格, category 商品销售分类\', attr_data LONGTEXT DEFAULT NULL COMMENT \'属性值\', created INT NOT NULL, updated INT DEFAULT NULL, INDEX ix_item_id (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'新供应商商品属性表\' ');
        $this->addSql('CREATE INDEX ix_supplier_id ON supplier_items (supplier_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
