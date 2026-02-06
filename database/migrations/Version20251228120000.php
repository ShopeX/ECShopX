<?php declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Change fp_salesperson field type from INT to VARCHAR
 * 
 * 修改分配导购字段类型从整数改为字符串
 * - fp_salesperson: 从 INT 改为 VARCHAR(100)，用于存储导购员工编号(employee_number/work_userid)
 */
final class Version20251228120000 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // Drop index first
        $this->addSql('DROP INDEX idx_fp_salesperson ON members');
        
        // Change fp_salesperson field type from INT to VARCHAR(100)
        $this->addSql('ALTER TABLE members MODIFY fp_salesperson VARCHAR(100) DEFAULT NULL COMMENT \'分配的导购员工编号(employee_number/work_userid)\'');
        
        // Recreate index for better query performance
        $this->addSql('CREATE INDEX idx_fp_salesperson ON members (fp_salesperson)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // Drop index
        $this->addSql('DROP INDEX idx_fp_salesperson ON members');
        
        // Change fp_salesperson field type back from VARCHAR to INT
        $this->addSql('ALTER TABLE members MODIFY fp_salesperson INT DEFAULT 0 COMMENT \'分配的导购ID\'');
        
        // Recreate index
        $this->addSql('CREATE INDEX idx_fp_salesperson ON members (fp_salesperson)');
    }
}

