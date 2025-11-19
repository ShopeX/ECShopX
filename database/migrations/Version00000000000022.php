<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000022 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE companys_roles ADD distributor_id BIGINT DEFAULT 0 NOT NULL COMMENT \'店铺id。为0则代表是平台添加的店铺角色\'');
        $this->addSql('DELETE FROM aliyunsms_scene WHERE scene_name=\'订单提货码\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
