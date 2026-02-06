<?php declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Add op_distributor field to members table
 * 
 * 添加分配的店铺字段到会员表
 * - op_distributor: 作为分配的店铺ID
 */
final class Version20251227120000 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // Add op_distributor field (作为分配的店铺ID)
        $this->addSql('ALTER TABLE members ADD op_distributor INT DEFAULT 0 COMMENT \'作为分配的店铺ID\'');
        
        // Add index for better query performance
        $this->addSql('CREATE INDEX idx_op_distributor ON members (op_distributor)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // Drop index
        $this->addSql('DROP INDEX idx_op_distributor ON members');
        
        // Drop column
        $this->addSql('ALTER TABLE members DROP op_distributor');
    }
}

