<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20251212151424 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // 创建设计师作品与商品绑定关系表
        $this->addSql('CREATE TABLE kujiale_designer_works_item_rel (
            id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'id\',
            item_id BIGINT NOT NULL COMMENT \'商品ID\',
            design_id VARCHAR(255) NOT NULL COMMENT \'设计ID\',
            goods_bn VARCHAR(255) DEFAULT NULL COMMENT \'SPU货号\',
            created INT NOT NULL COMMENT \'创建时间\',
            updated INT DEFAULT NULL COMMENT \'更新时间\',
            INDEX idx_item_id (item_id),
            INDEX idx_design_id (design_id),
            INDEX idx_goods_bn (goods_bn),
            UNIQUE KEY uk_design_id (design_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'设计师作品与商品绑定关系表\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE IF EXISTS kujiale_designer_works_item_rel');
    }
}
