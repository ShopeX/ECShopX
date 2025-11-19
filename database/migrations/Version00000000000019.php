<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000019 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE orders_merchant_trade CHANGE payment_no payment_no VARCHAR(64) DEFAULT NULL COMMENT \'微信支付订单号\'');
        $this->addSql('ALTER TABLE pages_open_screen_ad CHANGE ad_url ad_url VARCHAR(1000) NOT NULL COMMENT \'广告链接\'');
        $this->addSql('ALTER TABLE promotions_employee_purchase CHANGE minimum_amount minimum_amount INT UNSIGNED NOT NULL COMMENT \'起定金额，以分为单位\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
