<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20251226130000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE kaquan_discount_cards ADD coupon_type VARCHAR(10) DEFAULT \'mall\' NOT NULL COMMENT \'券类型。mall:商城券;guide:导购专属券\'');
        $this->addSql('ALTER TABLE kaquan_discount_cards ADD guide_issue_quantity INT DEFAULT 0 NOT NULL COMMENT \'导购发放数量\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE kaquan_discount_cards DROP coupon_type');
        $this->addSql('ALTER TABLE kaquan_discount_cards DROP guide_issue_quantity');
    }
}

