<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000051 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE items ADD delivery_time INT DEFAULT 0 COMMENT \'发货时间，如2，表示2天发货\', ADD is_taobao INT DEFAULT 0 COMMENT \'是否淘宝商品\'');
        $this->addSql('ALTER TABLE items_category ADD category_id_taobao BIGINT DEFAULT 0 COMMENT \'淘宝分类id\', ADD parent_id_taobao BIGINT DEFAULT 0 COMMENT \'淘宝父级分类ID\', ADD taobao_category_info JSON DEFAULT NULL COMMENT \'淘宝分类信息行(DC2Type:json_array)\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
