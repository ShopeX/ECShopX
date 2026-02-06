<?php declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Add fp_salesperson and has_fp fields to members table
 * 
 * 添加分配导购相关字段到会员表
 * - fp_salesperson: 分配的导购ID
 * - has_fp: 是否有分配导购（0:否，1:是）
 */
final class Version20250126120000 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // Add fp_salesperson field (分配的导购ID)
        $this->addSql('ALTER TABLE members ADD fp_salesperson INT DEFAULT 0 COMMENT \'分配的导购ID\'');
        
        // Add has_fp field (是否有分配导购)
        $this->addSql('ALTER TABLE members ADD has_fp TINYINT(1) DEFAULT 0 COMMENT \'是否有分配导购。0:否；1:是\'');
        
        // Add index for better query performance
        $this->addSql('CREATE INDEX idx_fp_salesperson ON members (fp_salesperson)');
        $this->addSql('CREATE INDEX idx_has_fp ON members (has_fp)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // Drop indexes
        $this->addSql('DROP INDEX idx_fp_salesperson ON members');
        $this->addSql('DROP INDEX idx_has_fp ON members');
        
        // Drop columns
        $this->addSql('ALTER TABLE members DROP fp_salesperson');
        $this->addSql('ALTER TABLE members DROP has_fp');
    }
}

