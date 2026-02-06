<?php declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Add is_become_friend field to members table
 * 
 * 添加导购已加好友字段到会员表
 * - is_become_friend: 是否已加为好友（0:否，1:是）
 */
final class Version20251226120000 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // Add is_become_friend field (是否已加为好友)
        $this->addSql('ALTER TABLE members ADD is_become_friend TINYINT(1) DEFAULT 0 COMMENT \'是否已加为好友。0:否；1:是\'');
        
        // Add index for better query performance
        $this->addSql('CREATE INDEX idx_is_become_friend ON members (is_become_friend)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // Drop index
        $this->addSql('DROP INDEX idx_is_become_friend ON members');
        
        // Drop column
        $this->addSql('ALTER TABLE members DROP is_become_friend');
    }
}

