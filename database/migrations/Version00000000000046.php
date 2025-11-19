<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000046 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE distribution_distributor_white_list (id BIGINT AUTO_INCREMENT NOT NULL, distributor_id BIGINT DEFAULT 1 NOT NULL COMMENT \'店铺id\', company_id BIGINT NOT NULL COMMENT \'公司id\', mobile VARCHAR(50) NOT NULL COMMENT \'店铺手机号\', username VARCHAR(255) DEFAULT NULL COMMENT \'名称\', created bigint NOT NULL, updated bigint NOT NULL, INDEX ix_distributor_id (distributor_id), INDEX ix_mobile (mobile), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'店铺表\' ');
        $this->addSql('ALTER TABLE distribution_distributor ADD open_divided BIGINT DEFAULT 0 NOT NULL COMMENT \'是否开启店铺隔离\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
