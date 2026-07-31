<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20260731205807 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE supplier_items_draft (draft_id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'待审草稿ID\', source_item_id BIGINT NOT NULL COMMENT \'主表商品SKU ID\', goods_id BIGINT NOT NULL COMMENT \'SPU ID\', company_id BIGINT NOT NULL COMMENT \'公司ID\', supplier_id BIGINT DEFAULT 0 NOT NULL COMMENT \'供应商ID\', default_item_id BIGINT DEFAULT NULL COMMENT \'默认SKU主表ID\', is_default SMALLINT DEFAULT 0 NOT NULL COMMENT \'是否默认SKU\', content_json LONGTEXT NOT NULL COMMENT \'待审商品内容JSON\', created INT NOT NULL, updated INT DEFAULT NULL, INDEX ix_source_item_id (source_item_id), INDEX ix_goods_id (goods_id), INDEX ix_company_goods (company_id, goods_id), PRIMARY KEY(draft_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'供应商商品待审草稿表\' ');
        $this->addSql('ALTER TABLE members_associations CHANGE unionid unionid VARCHAR(128) NOT NULL COMMENT \'第三方unionid\', CHANGE user_type user_type VARCHAR(30) NOT NULL COMMENT \'用户类型，可选值有 wechat:微信;ali:支付宝;apple;google;facebook;line\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}