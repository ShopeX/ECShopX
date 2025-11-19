<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20250826174401 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // 添加重试次数字段到 orders_invoice 表
        $this->addSql('ALTER TABLE orders_invoice ADD COLUMN try_times INT DEFAULT 0 COMMENT "重试次数"');
        
        // 为现有数据初始化重试次数字段值
        // $this->addSql('UPDATE orders_invoice SET try_times = 0 WHERE try_times IS NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // 删除重试次数字段
        $this->addSql('ALTER TABLE orders_invoice DROP COLUMN try_times');
    }
}
