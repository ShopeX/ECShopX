<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20260327161931 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE distribution_distributor_category (category_id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'分类ID\', company_id BIGINT NOT NULL COMMENT \'公司ID\', category_name VARCHAR(50) NOT NULL COMMENT \'店铺分类名称\', category_code VARCHAR(50) NOT NULL COMMENT \'分类编号\', created bigint NOT NULL, updated bigint NOT NULL, INDEX idx_company_id (company_id), INDEX idx_category_name (category_name), INDEX idx_category_code (category_code), PRIMARY KEY(category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'店铺分类表\' ');
        $this->addSql('ALTER TABLE distribution_distributor ADD show_mobile SMALLINT DEFAULT 1 NOT NULL COMMENT \'是否展示手机号 1:展示 0:不展示\', ADD show_salesperson SMALLINT DEFAULT 1 NOT NULL COMMENT \'是否展示导购 0:不展示 1:展示固定URL 2:展示归属导购\', ADD fixed_salesperson_qrcode_url VARCHAR(255) DEFAULT NULL COMMENT \'导购固定码URL\', ADD distributor_category_id BIGINT DEFAULT 0 NOT NULL COMMENT \'店铺分类id\'');
        $this->addSql('DROP INDEX ix_company_id ON items_category');
        $this->addSql('DROP INDEX ix_is_main_category ON items_category');
        $this->addSql('ALTER TABLE items_category ADD is_show_front SMALLINT DEFAULT 1 NOT NULL COMMENT \'是否前台展示\'');
        $this->addSql('CREATE INDEX ix_company_main_show ON items_category (company_id, is_main_category, is_show_front)');
        $this->addSql('ALTER TABLE operators ADD shopex_bind_account VARCHAR(255) DEFAULT NULL COMMENT \'可选绑定 Shopex 登录账号\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
