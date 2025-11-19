<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000020 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE chinaumspay_division (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'ID\', company_id BIGINT NOT NULL COMMENT \'公司id\', distributor_id BIGINT NOT NULL COMMENT \'店铺ID\', total_fee VARCHAR(255) NOT NULL COMMENT \'订单金额，以分为单位\', actual_fee VARCHAR(255) NOT NULL COMMENT \'订单实际金额，以分为单位\', commission_rate_fee INT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'收单手续费金额，以分为单位\', division_fee INT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'分账金额，以分为单位\', create_time INT NOT NULL COMMENT \'订单创建时间\', update_time INT DEFAULT NULL COMMENT \'订单更新时间\', INDEX idx_company (company_id), INDEX idx_distributor_id (distributor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'银联商务支付分账流水\' ');
        $this->addSql('CREATE TABLE chinaumspay_division_detail (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'ID\', company_id BIGINT NOT NULL COMMENT \'公司id\', division_id BIGINT NOT NULL COMMENT \'分账流水ID\', order_id BIGINT NOT NULL COMMENT \'订单号\', distributor_id BIGINT NOT NULL COMMENT \'店铺ID\', total_fee VARCHAR(255) NOT NULL COMMENT \'订单金额，以分为单位\', actual_fee VARCHAR(255) NOT NULL COMMENT \'订单实际金额，以分为单位\', commission_rate DOUBLE PRECISION NOT NULL COMMENT \'收单手续费费率\', commission_rate_fee INT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'收单手续费金额，以分为单位\', division_fee INT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'分账金额，以分为单位\', create_time INT NOT NULL COMMENT \'订单创建时间\', update_time INT DEFAULT NULL COMMENT \'订单更新时间\', INDEX idx_company (company_id), INDEX idx_division_id (division_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'银联商务支付分账流水明细\' ');
        $this->addSql('CREATE TABLE chinaumspay_division_error_log (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'ID\', company_id BIGINT NOT NULL COMMENT \'公司id\', division_id BIGINT NOT NULL COMMENT \'分账流水ID\', upload_detail_id BIGINT NOT NULL COMMENT \'上传明细ID\', type VARCHAR(50) NOT NULL COMMENT \'类型 division:分账;transfer:划付;\', distributor_id BIGINT NOT NULL COMMENT \'店铺ID\', status VARCHAR(20) DEFAULT NULL COMMENT \'错误状态 0:未处理、1:处理中、2:成功、3:部分成功、4:失败\', error_desc LONGTEXT DEFAULT NULL COMMENT \'错误描述\', is_resubmit TINYINT(1) DEFAULT \'0\' COMMENT \'是否重新提交\', create_time INT NOT NULL COMMENT \'订单创建时间\', update_time INT DEFAULT NULL COMMENT \'订单更新时间\', INDEX idx_company (company_id), INDEX idx_division_id (division_id), INDEX idx_type (type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'银联商务支付分账错误日志\' ');
        $this->addSql('CREATE TABLE chinaumspay_division_upload_detail (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'ID\', company_id BIGINT NOT NULL COMMENT \'公司id\', division_id BIGINT NOT NULL COMMENT \'分账流水ID\', distributor_id BIGINT NOT NULL COMMENT \'店铺ID\', file_type VARCHAR(50) NOT NULL COMMENT \'文件类型 division:分账;transfer:划付;\', detail VARCHAR(255) NOT NULL COMMENT \'分账明细\', times INT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'上传次数\', backsucc_fee INT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'回盘成功金额，以分为单位\', rate_fee INT UNSIGNED DEFAULT 0 NOT NULL COMMENT \'银联商务该笔指令收取的业务处理费，以分为单位\', back_status VARCHAR(255) DEFAULT NULL COMMENT \'回盘状态 0:未处理、1:处理中、2:成功、3:部分成功、4:失败\', back_status_msg VARCHAR(255) DEFAULT NULL COMMENT \'回盘状态描述\', chinaumspay_id VARCHAR(255) DEFAULT NULL COMMENT \'银商内部ID\', create_time INT NOT NULL COMMENT \'订单创建时间\', update_time INT DEFAULT NULL COMMENT \'订单更新时间\', INDEX idx_company (company_id), INDEX idx_division_id (division_id), INDEX idx_distributor_id (distributor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'银联商务支付sftp上传明细\' ');
        $this->addSql('CREATE TABLE chinaumspay_division_upload_log (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'ID\', company_id BIGINT NOT NULL COMMENT \'公司id\', file_type VARCHAR(50) NOT NULL COMMENT \'文件类型 division:分账;transfer:划付;\', local_file_path VARCHAR(50) NOT NULL COMMENT \'本地文件路径\', remote_file_path VARCHAR(50) NOT NULL COMMENT \'远程文件路径\', file_name VARCHAR(50) NOT NULL COMMENT \'文件名\', file_content LONGTEXT NOT NULL COMMENT \'文件内容\', back_status VARCHAR(255) DEFAULT NULL COMMENT \'回盘状态 0:未回盘;1:已回盘;\', create_time INT NOT NULL COMMENT \'订单创建时间\', update_time INT DEFAULT NULL COMMENT \'订单更新时间\', INDEX idx_company (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'银联商务支付上传文件日志\' ');
        $this->addSql('CREATE TABLE orders_rel_chinaumspay_division (id BIGINT AUTO_INCREMENT NOT NULL COMMENT \'ID\', order_id BIGINT NOT NULL COMMENT \'订单号\', company_id BIGINT NOT NULL COMMENT \'公司id\', status VARCHAR(255) DEFAULT NULL COMMENT \'划付状态 0:待处理、1:已上传、2:无需处理\', create_time INT NOT NULL COMMENT \'订单创建时间\', update_time INT DEFAULT NULL COMMENT \'订单更新时间\', INDEX idx_company (company_id), INDEX idx_order_id (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'订单关联银联商务分账表\' ');
        $this->addSql('ALTER TABLE items CHANGE approve_status approve_status VARCHAR(255) NOT NULL COMMENT \'商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售, only_show 前台仅展示\'');
        $this->addSql('ALTER TABLE pointsmall_items CHANGE approve_status approve_status VARCHAR(255) NOT NULL COMMENT \'商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
