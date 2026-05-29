<?php

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema as Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20260519000001 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql("ALTER TABLE web_menu_items ADD image_url VARCHAR(500) DEFAULT NULL COMMENT '菜单项图片' AFTER name");
        $this->addSql("ALTER TABLE web_menu_items ADD link_extra LONGTEXT DEFAULT NULL COMMENT '链接扩展信息(JSON)' AFTER link_value");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('ALTER TABLE web_menu_items DROP image_url, DROP link_extra');
    }
}
