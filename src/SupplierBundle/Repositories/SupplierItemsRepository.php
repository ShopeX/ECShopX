<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace SupplierBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangService;
use SupplierBundle\Entities\SupplierItems;

class SupplierItemsRepository extends BaseRepository
{
    use MagicLangTrait;
    public $table = "supplier_items";
    public $cols = [
        'item_id', 'item_type', 'consume_type', 'is_show_specimg','store', 'barcode', 'sales', 'approve_status', 'rebate', 'rebate_conf', 'cost_price','is_point', 'point', 'item_source', 'goods_id', 'brand_id', 'is_market',
        'consume_type', 'item_name', 'item_unit', 'item_bn', 'brief', 'price', 'market_price', 'special_type', 'goods_function', 'goods_series', 'volume', 'supplier_id',
        'goods_color', 'goods_brand', 'item_address_province', 'item_address_city', 'regions_id', 'regions', 'brand_logo', 'sort', 'templates_id', 'is_default', 'nospec', 'default_item_id', 'pics', 'pics_create_qrcode', 'distributor_id',
        'company_id', 'enable_agreement', 'date_type', 'item_category', 'rebate_type', 'weight', 'begin_date', 'end_date', 'fixed_term','tax_rate', 'created', 'updated', 'video_type', 'videos', 'video_pic_url', 'purchase_agreement',
        'intro', 'audit_status', 'audit_reason', 'is_gift', 'is_package', 'profit_type', 'profit_fee', 'is_profit','crossborder_tax_rate','origincountry_id','taxstrategy_id','taxation_num','type','tdk_content','is_epidemic',
        'goods_bn','supplier_goods_bn','audit_date','is_medicine','is_prescription','start_num'
    ];
    private $prk = 'item_id';

    private $multiLangField = [
        'item_name','brief','intro'
    ];
    /**
     * 新增
     *
     * @param array $data
     * @return array
     */
    public function create($data)
    {
        $entity = new SupplierItems();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $result = $this->getColumnNamesData($entity);
        $service = new MultiLangService();
        $service->addMultiLangByParams($result['item_id'],$data,'items');
        return $result;
    }

    /**
     * 获取指定条件的所有商品列表，可指定字段
     */
    public function getItemsLists($filter, $cols = 'item_id, default_item_id')
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($filter, $qb);
        $lists = $qb->execute()->fetchAll();
        $service = new MultiLangService();
        $lists = $service->getListAddLang($lists,$this->multiLangField,'items',$this->getLang(),'item_id');
        return $lists;
    }
}
