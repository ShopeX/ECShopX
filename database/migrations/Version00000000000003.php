<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000003 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE distribution_distributor_sales_count (id BIGINT AUTO_INCREMENT NOT NULL, company_id BIGINT NOT NULL COMMENT \'公司id\', distributor_id BIGINT NOT NULL COMMENT \'店铺id\', order_item_count BIGINT DEFAULT 0 NOT NULL COMMENT \'已经关闭售后的订单的商品数量\', aftersales_item_count BIGINT DEFAULT 0 NOT NULL COMMENT \'已经关闭售后的订单的商品数量\', year_month_time BIGINT NOT NULL COMMENT \'统计的年月时间\', INDEX ix_company_id_distributor_id_time (company_id, distributor_id, year_month_time), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'店铺销量表\' ');
        $this->addSql('DROP INDEX idx_card_no ON adapay_corp_member');
        $this->addSql('CREATE INDEX idx_card_no ON adapay_corp_member (card_no(64))');
        $this->addSql('DROP INDEX idx_user_name ON adapay_member');
        $this->addSql('DROP INDEX idx_cert_id ON adapay_member');
        $this->addSql('DROP INDEX idx_tel_no ON adapay_member');
        $this->addSql('CREATE INDEX idx_user_name ON adapay_member (user_name(64))');
        $this->addSql('CREATE INDEX idx_cert_id ON adapay_member (cert_id(64))');
        $this->addSql('CREATE INDEX idx_tel_no ON adapay_member (tel_no(64))');
        $this->addSql('DROP INDEX idx_card_id ON adapay_settle_account');
        $this->addSql('DROP INDEX idx_cert_id ON adapay_settle_account');
        $this->addSql('CREATE INDEX idx_card_id ON adapay_settle_account (card_id(64))');
        $this->addSql('CREATE INDEX idx_cert_id ON adapay_settle_account (cert_id(64))');
        $this->addSql('ALTER TABLE items DROP item_en_name');
        $this->addSql('DROP INDEX idx_mobile ON members');
        $this->addSql('CREATE INDEX idx_mobile ON members (mobile(64))');
        $this->addSql('DROP INDEX idx_mobile ON members_whitelist');
        $this->addSql('CREATE INDEX idx_mobile ON members_whitelist (mobile(64))');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
