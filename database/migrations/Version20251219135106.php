<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20251219135106 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE aftersales ADD freight_type VARCHAR(10) DEFAULT \'cash\' NOT NULL COMMENT \'运费类型-用于积分商城 cash:现金 point:积分\'');
        $this->addSql('ALTER TABLE aftersales_refund ADD freight_type VARCHAR(10) DEFAULT \'cash\' NOT NULL COMMENT \'运费类型-用于积分商城 cash:现金 point:积分\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

    }
}
