<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20260410120000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE web_menus (id INT UNSIGNED AUTO_INCREMENT NOT NULL, company_id INT UNSIGNED NOT NULL, name VARCHAR(100) NOT NULL COMMENT \'菜单名称（如：顶部导航）\', `key` VARCHAR(100) NOT NULL COMMENT \'菜单标识符（前端调用）\', status TINYINT(1) DEFAULT 1 NOT NULL COMMENT \'1=启用 0=禁用\', created_at DATETIME NOT NULL COMMENT \'创建时间\', updated_at DATETIME NOT NULL COMMENT \'更新时间\', UNIQUE INDEX uk_company_key (company_id, `key`), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'Web端商城-菜单主表\'');
        $this->addSql('CREATE TABLE web_menu_items (id INT UNSIGNED AUTO_INCREMENT NOT NULL, menu_id INT UNSIGNED NOT NULL, company_id INT UNSIGNED NOT NULL, parent_id INT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'0=顶级菜单项\', name VARCHAR(100) NOT NULL COMMENT \'菜单项显示名称\', link_type VARCHAR(50) DEFAULT \'url\' NOT NULL COMMENT \'category|custom_page|list_page|article|url|none\', link_value VARCHAR(500) DEFAULT NULL COMMENT \'关联目标值（ID 或 URL）\', sort INT DEFAULT 0 NOT NULL, status TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX idx_menu_id (menu_id), INDEX idx_company_id (company_id), INDEX idx_parent_id (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'Web端商城-菜单项表\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE web_menu_items');
        $this->addSql('DROP TABLE web_menus');
    }
}
