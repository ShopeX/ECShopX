<?php declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251225120000 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // Add reg_distributor and reg_salesperson to members table
        $this->addSql('ALTER TABLE members ADD reg_distributor INT DEFAULT 0 COMMENT \'注册时的分销商ID\'');
        $this->addSql('ALTER TABLE members ADD reg_salesperson VARCHAR(100) DEFAULT NULL COMMENT \'注册时的导购ID\'');
        
        // Add indexes for better query performance
        $this->addSql('CREATE INDEX idx_reg_distributor ON members (reg_distributor)');
        $this->addSql('CREATE INDEX idx_reg_salesperson ON members (reg_salesperson)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX idx_reg_distributor ON members');
        $this->addSql('DROP INDEX idx_reg_salesperson ON members');
        $this->addSql('ALTER TABLE members DROP reg_distributor');
        $this->addSql('ALTER TABLE members DROP reg_salesperson');
    }
}

