<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000011 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE failed_jobs (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'id\', connection LONGTEXT DEFAULT NULL, queue LONGTEXT DEFAULT NULL, payload LONGTEXT DEFAULT NULL, exception LONGTEXT DEFAULT NULL, failed_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB COMMENT = \'失败队列记录表\' ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
