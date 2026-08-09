<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20260804170000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE selfservice_registration_activity ADD is_show INT NOT NULL DEFAULT 1 COMMENT \'是否展示(1展示 0隐藏)\'');
        // 历史数据自动为 1（展示），不影响线上已有活动
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
