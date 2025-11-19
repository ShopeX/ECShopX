<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version00000000000041 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO supplier_items (item_id,item_type,item_category,consume_type,item_name,item_bn,barcode,brief,company_id,price,cost_price,item_unit,special_type,item_address_province,item_address_city,regions_id,regions,store,sales,rebate_conf,rebate,rebate_type,approve_status,audit_status,audit_reason,market_price,goods_function,goods_series,goods_color,goods_brand,is_default,default_item_id,goods_id,nospec,weight,sort,is_epidemic,templates_id,pics,pics_create_qrcode,video_type,videos,video_pic_url,intro,purchase_agreement,is_show_specimg,enable_agreement,date_type,begin_date,end_date,fixed_term,brand_logo,is_point,point,distributor_id,volume,item_source,brand_id,tax_rate,crossborder_tax_rate,profit_type,origincountry_id,taxstrategy_id,taxation_num,profit_fee,type,is_profit,created,updated,is_gift,is_package,tdk_content,supplier_id,is_market,goods_bn,supplier_goods_bn,audit_date) SELECT item_id,item_type,item_category,consume_type,item_name,item_bn,barcode,brief,company_id,price,cost_price,item_unit,special_type,item_address_province,item_address_city,regions_id,regions,store,sales,rebate_conf,rebate,rebate_type,approve_status,audit_status,audit_reason,market_price,goods_function,goods_series,goods_color,goods_brand,is_default,default_item_id,goods_id,nospec,weight,sort,is_epidemic,templates_id,pics,pics_create_qrcode,video_type,videos,video_pic_url,intro,purchase_agreement,is_show_specimg,enable_agreement,date_type,begin_date,end_date,fixed_term,brand_logo,is_point,point,distributor_id,volume,item_source,brand_id,tax_rate,crossborder_tax_rate,profit_type,origincountry_id,taxstrategy_id,taxation_num,profit_fee,type,is_profit,created,updated,is_gift,is_package,tdk_content,supplier_id,is_market,goods_bn,supplier_goods_bn,audit_date FROM items WHERE item_id NOT IN(SELECT item_id FROM supplier_items)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
