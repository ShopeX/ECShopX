<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000035 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE espier_export_log CHANGE file_url file_url LONGTEXT DEFAULT NULL COMMENT \'导出文件下载路径\'');
        $this->addSql('ALTER TABLE trade ADD payment_params LONGTEXT DEFAULT NULL COMMENT \'支付参数\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
