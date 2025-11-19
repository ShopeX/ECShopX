<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000023 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE companys_protocol_update_log CHANGE content content LONGTEXT DEFAULT NULL COMMENT \'协议详细内容\'');
        $this->addSql('ALTER TABLE distribution_distributor ADD delivery_distance INT DEFAULT 0 NOT NULL COMMENT \'配送距离\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
