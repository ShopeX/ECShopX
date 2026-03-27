<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20251210125745 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // 创建设计师作品城市关联表
        $this->addSql('CREATE TABLE kujiale_designer_works_rel_cities (
            id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'自增ID\',
            design_id VARCHAR(255) NOT NULL COMMENT \'方案ID\',
            plan_id VARCHAR(255) DEFAULT NULL COMMENT \'户型ID\',
            city_id INT NOT NULL COMMENT \'城市ID\',
            created INT NOT NULL COMMENT \'创建时间\',
            updated INT DEFAULT NULL COMMENT \'更新时间\',
            INDEX idx_design_id (design_id),
            INDEX idx_plan_id (plan_id),
            INDEX idx_city_id (city_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'设计师作品城市关联表\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE IF EXISTS kujiale_designer_works_rel_cities');
    }
}

