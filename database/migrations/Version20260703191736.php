<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20260703191736 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bspay_user_card ADD branch_code VARCHAR(12) DEFAULT \'\' COMMENT \'支行联行号，对公必填\', DROP bank_code');
        $this->addSql('ALTER TABLE kaquan_user_discount_logs CHANGE mobile mobile VARCHAR(255) DEFAULT NULL COMMENT \'手机号\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}