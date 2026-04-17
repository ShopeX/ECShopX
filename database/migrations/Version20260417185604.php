<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20260417185604 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE member_email_activation_tokens (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'主键\', company_id BIGINT NOT NULL COMMENT \'公司 ID\', user_id BIGINT NOT NULL COMMENT \'会员 user_id\', token_hash VARCHAR(64) NOT NULL COMMENT \'SHA-256 哈希\', expires_at INT NOT NULL COMMENT \'过期时间戳\', used_at INT DEFAULT NULL COMMENT \'使用时间戳\', created_at INT NOT NULL COMMENT \'创建时间戳\', INDEX idx_company_user (company_id, user_id), INDEX idx_token_hash (token_hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'会员邮箱激活链接令牌\' ');
        $this->addSql('CREATE TABLE member_password_reset_tokens (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'主键\', company_id BIGINT NOT NULL COMMENT \'公司 ID\', user_id BIGINT NOT NULL COMMENT \'会员 user_id\', token_hash VARCHAR(64) NOT NULL COMMENT \'SHA-256 哈希\', expires_at INT NOT NULL COMMENT \'过期时间戳\', used_at INT DEFAULT NULL COMMENT \'使用时间戳\', created_at INT NOT NULL COMMENT \'创建时间戳\', INDEX idx_company_user (company_id, user_id), INDEX idx_token_hash (token_hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'会员邮箱找回密码令牌\' ');
        $this->addSql('ALTER TABLE members ADD login_email VARCHAR(255) DEFAULT NULL COMMENT \'登录邮箱(小写)\', ADD email_verified_at INT DEFAULT NULL COMMENT \'邮箱验证时间戳\'');
        $this->addSql('CREATE UNIQUE INDEX login_email_company ON members (login_email, company_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
