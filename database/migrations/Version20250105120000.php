<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250105120000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '添加 salesperson_code 字段到 kaquan_user_discount 表';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE kaquan_user_discount ADD salesperson_code VARCHAR(100) DEFAULT NULL COMMENT \'导购编号(employee_number/work_userid)\' AFTER salesperson_id');
        $this->addSql('CREATE INDEX idx_salesperson_code ON kaquan_user_discount (salesperson_code)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('DROP INDEX idx_salesperson_code ON kaquan_user_discount');
        $this->addSql('ALTER TABLE kaquan_user_discount DROP salesperson_code');
    }
}

