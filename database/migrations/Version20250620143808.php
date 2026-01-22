<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20250620143808 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        
        $this->addSql('ALTER TABLE items_category ADD category_id_taobao BIGINT DEFAULT 0 COMMENT \'淘宝分类id\', ADD parent_id_taobao BIGINT DEFAULT 0 COMMENT \'淘宝父级分类ID\', ADD taobao_category_info JSON DEFAULT NULL COMMENT \'淘宝分类信息行(DC2Type:json_array)\'');
        
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        
        // $this->addSql('ALTER TABLE items_category DROP category_id_taobao, DROP parent_id_taobao, DROP taobao_category_info');
        
    }
}
