<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20260126175248 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // 删除 kujiale_designer_works_item_rel 表的唯一索引 uk_design_id
        $this->addSql('ALTER TABLE kujiale_designer_works_item_rel DROP INDEX uk_design_id');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // 恢复唯一索引 uk_design_id
        $this->addSql('ALTER TABLE kujiale_designer_works_item_rel ADD UNIQUE KEY uk_design_id (design_id)');
    }
}
