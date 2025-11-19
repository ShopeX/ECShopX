<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000049 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE member_outfit (id BIGINT AUTO_INCREMENT NOT NULL, member_id INT NOT NULL, model_image VARCHAR(255) NOT NULL, status SMALLINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE member_outfit_log (id BIGINT AUTO_INCREMENT NOT NULL, model_id BIGINT DEFAULT NULL, member_id INT NOT NULL, item_id INT DEFAULT NULL, request_id VARCHAR(64) NOT NULL, top_garment_url VARCHAR(255) DEFAULT NULL, bottom_garment_url VARCHAR(255) DEFAULT NULL, result_url VARCHAR(255) DEFAULT NULL, status SMALLINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_92D9FFF3427EB8A5 (request_id), INDEX IDX_92D9FFF37975B7E7 (model_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE member_outfit_log ADD CONSTRAINT FK_92D9FFF37975B7E7 FOREIGN KEY (model_id) REFERENCES member_outfit (id)');
        $this->addSql('ALTER TABLE companys_article ADD is_ai TINYINT(1) DEFAULT \'0\' NOT NULL COMMENT \'是否AI生成，0表示人工创建，1表示AI生成\'');
        $this->addSql('ALTER TABLE selfservice_registration_record ADD remark LONGTEXT DEFAULT NULL COMMENT \'备注\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
