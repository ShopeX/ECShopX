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

namespace TbItemsBundle\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use TbItemsBundle\Client\TbClient;
use GoodsBundle\Services\ItemsCategoryService;
use OrdersBundle\Services\ShippingTemplatesService;
use GoodsBundle\Services\ItemsAttributesService;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsRelCatsService;

class TbItemsService
{
    protected $tbClient;
    protected $config;
    protected $companyId;
    protected $table = 'tb_items';
    protected $skusTable = 'tb_items_skus';
    protected $connect;
    protected $brandId;
    protected $mainCategoryId;
    protected $templatesId;
    protected $itemAttributeArr = [];
    protected $fillIids = [];
    

    public function __construct($companyId)
    {
        // 初始化淘宝客户端
        $this->tbClient = new TbClient();
        $this->companyId = $companyId;
        $this->connect   = app('registry')->getConnection('default');
    }

    /**
     * 淘宝分类格式转换为分类数据格式
     */
    private function transformCategoryData($categoryData, $categoryList, $isMainCategory = 0)
    {
        // ShopEx EcShopX Service Component
        $transformedCategories = [];
        foreach ($categoryData as $item) {
            $categoryId = $categoryList[$item['cid']]['category_id'] ?? 0;
            $transformedCategories[] = [
                'category_id_taobao' => $item['cid'],
                'category_name' => $item['name'],
                'sort' => $item['sort_order'],  
                // 'category_level' => $item['parent_cid'] ? 2 : 1,
                'children' => [],
                'created' => time(),
                'image_url' => '',
                'goods_params' => [],
                'parent_id_taobao' => $item['parent_cid'],
                'taobao_category_info' => $item,
                'category_id' => $categoryId,
                'is_main_category' => $isMainCategory,
            ];
        }
        return $transformedCategories;
    }

    /**
     * 分类同步
     */
    public function syncItemsCategory()
    {
        return $this;
        $page = 1;
        do {
            try {
                $categoryObj = $this->tbClient->getTbCategoryListing([], $page, 1000);
                $category = json_decode($categoryObj, true);
                
                if ($category['rsp'] !== 'succ') {
                    throw new \Exception('淘宝端无分类数据，或接口异常，请联系管理员！');
                }

                $categoryData = $category['data'] ?? [];
                if (!$categoryData) {
                    break; // 没有更多数据，退出循环
                }
                // 分类数据格式 '[{"id":1611902635,"category_name":"热销商品","sort":0,"category_level":1,"children":[{"id":1611902644,"category_name":"热销","sort":0,"category_level":2,"children":[{"id":1611902663,"category_name":"爆品","sort":0,"category_level":3,"children":[],"created":-1,"image_url":"","is_main_category":true,"goods_params":[],"parent_id":1611902635}],"created":-1,"image_url":"","is_main_category":true,"goods_params":[],"parent_id":1611902635}],"created":-1,"image_url":"","is_main_category":true,"goods_params":[]}]'
               
                //淘宝分类格式
                // {"item_cat":[{"cid":50011999,"features":{"feature":[{"attr_key":"1234","attr_value":"2342"}]},"is_parent":true,"name":"单方精油","parent_cid":0,"sort_order":1,"status":"normal","taosir_cat":false}]}

                $itemsCategoryService = new ItemsCategoryService;
                $categoryListData = $itemsCategoryService->lists(['company_id' => $this->companyId], [], -1, 1, '*') ?? [];

                $categoryList = array_column($categoryListData['list'], null, 'category_id_taobao') ?? [];
                // 淘宝分类格式转换为分类数据格式   
                $transformedCategories = $this->transformCategoryData($categoryData, $categoryList, 1);
                // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '转换后的分类数据' . json_encode($transformedCategories));
                $treeCategory = make_tree_category($transformedCategories, 'category_id_taobao', 'parent_id_taobao', 'children');

                $itemsCategoryService->saveItemsCategory($treeCategory, $this->companyId, 0);

                $page++;
                
            } catch (Exception $e) {
                app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步分类数据失败', [
                    'page' => $page,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }

            $categoryData = [];
        } while (!empty($categoryData)); // 继续循环直到没有更多数据
        
        // Log::info('分类同步完成：新增 ' . $totalInserted . ' 个，更新 ' . $totalUpdated . ' 个');
        return $this;
    }

    /**
     * 同步淘宝商品
     * store.items.all.get 获取店铺内出售中的商品列表
     */
    public function newSyncTbItems( $params = [] )
    {
        return $this;
        $page = 1;
        $spage = 1;
        $pageSize = 1;
        $total = 0;
        $items = [];
        $i = 0;

        do {
            try {
                $itemsObj = $this->tbClient->getTbItemsListing($params, $page, $pageSize);
                $items = json_decode($itemsObj, true);
                if ($items['rsp'] !== 'succ') {
                    throw new \Exception('淘宝端无商品数据，或接口异常，请联系管理员！');
                }

                $itemsData = $items['data'] ?? [];
                if (!$itemsData) {
                    break; // 没有更多数据，退出循环    
                }
                app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品数据' . json_encode($itemsData));
                //只记录skus
                foreach ($itemsData as $item) {
                    $iid = $item['iid'];
                    //如果有params
                    if(isset($params) && isset($params['start_modified']) && isset($params['end_modified'])){
                        $this->fillIids[] = $iid;
                    }
                    // $skus = $item['skus'];
                    $i++;
                    app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品数据iid' . $iid . '第' . $i . '条');
                    // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品数据skus' . json_encode($skus).'第' . $i . '条');
                }

                //先把spu落到临时表
                $this->spuExItems($itemsData, $itemsList);
                if ($spage == 1) {
                    array_first($itemsList, function ($v) {
                        $sql_columns = 'tb_iid BIGINT NOT NULL AUTO_INCREMENT, ';
                        foreach ($v as $columns => $val) {
                            $sql_columns .= $columns . ' VARCHAR(255) DEFAULT \'\', ';
                        }
                        $sql_columns .= 'handle_status VARCHAR(50) DEFAULT \'PENDING\', PRIMARY KEY ( tb_iid ), ';
                        $sql_columns .= 'INDEX idx_outer_id (outer_id), INDEX idx_tb_iid (tb_iid)';
                        $sql = 'CREATE TABLE IF NOT EXISTS ' . $this->table . ' (' . $sql_columns . ')ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
                        $this->connect->exec($sql);
                    });
                }

                $itemsBn   = array_column($itemsList, 'outer_id');
                if (!$itemsBn) {
                    $page++;
                    continue;
                }
                $itemsBn   = array_map(function($item) {
                    return "'" . $item . "'";
                }, $itemsBn);
                $itemsBn   = implode(',', $itemsBn);
                $bnslList = $this->connect->createQueryBuilder()
                    ->select('tb_iid,iid,outer_id')
                    ->from($this->table)
                    ->andWhere('outer_id IN(' . $itemsBn . ')')
                    ->execute()
                    ->fetchAll();
                $bnslList = array_column($bnslList, null, 'outer_id');
                $this->connect->beginTransaction();
                try {

                    foreach ($itemsList as $k => $item) {
                        if (!$item['cid']) {
                            continue;
                        }

                        array_walk_recursive($item, function (&$vv) {
                            $vv = trim(trim($vv, chr(0xc2) . chr(0xa0)));
                        });
                        if (isset($bnslList[$item['outer_id']])) {
                            $this->connect->update($this->table, ['iid'=>$item['iid']], ['handle_status' => 'PENDING']);
                        }else{
                            $this->connect->insert($this->table, $item);
                        }

                        if ($k % 10000 == 0) {
                            // 每1万条数据，提交一次插入记录
                            $this->connect->commit();
                            $this->connect->beginTransaction();
                        }
                    }
                    $this->connect->commit();
                } catch (\Exception $e) {
                    $this->connect->rollback();
                    app('log')->debug(__CLASS__ . __FUNCTION__ . __LINE__ . $e->getFile() . $e->getLine() . $e->getMessage());
                }
                
            } catch (Exception $e) {
                app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品失败', [
                    'page' => $page,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
            $page++;
            $spage++;
            if ($page > 2) {
                $items = [];
            }
        } while (!empty($items)); 
        
        return $this;
    }

    //先落库spu
    function spuExItems($itemsData, &$itemsList)
    {
        foreach($itemsData as $item){
            $newItem['title'] = $item['title'];
            $newItem['iid'] = $item['iid'];
            $newItem['outer_id'] = $item['outer_id'];
            $newItem['cid'] = $item['cid'];
            $newItem['price'] = $item['price'];
            $newItem['default_img_url'] = $item['default_img_url'];
            $newItem['shop_cids'] = $item['shop_cids'];
            $itemsList[] = $newItem;
        }

        return $this;
    }
    
    /**
     * 同步淘宝商品
     * store.items.list.get 根据商品ID批量获取商品信息
     * iid 每20个一批
     */
    function newSyncTbSkus()
    {
        $page = 1;
        $spage = 1;
        $pageSize = 20;
        $total = 0;
        $skus = [];
        $i = 0;

        do {
            try {
                // 获取spu tb_items表
                $spuObj = $this->connect->createQueryBuilder()
                    ->select('tb_iid,iid,outer_id')
                    ->from($this->table)
                    ->andWhere('handle_status = \'PENDING\'')
                    ->setMaxResults($pageSize)
                    ->setFirstResult(($page - 1) * $pageSize)
                    ->execute()
                    ->fetchAll();
                $iids = array_column($spuObj, 'iid');
                $iids = array_unique($iids);
                if (!$iids) {
                    break; // 没有更多数据，退出循环 
                }
                $iids = implode(',', $iids);

                // $iids = '860473034673';
                $skusObj = $this->tbClient->getTbSkusListing(['iids' => $iids], $page, $pageSize);
                $skus = json_decode($skusObj, true);
                if ($skus['rsp'] !== 'succ') {
                    throw new \Exception('淘宝端无商品数据，或接口异常，请联系管理员！');
                }

                $skusData = $skus['data'] ?? [];
                if (!$skusData) {
                    break; // 没有更多数据，退出循环    
                }
                app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品数据' . json_encode($skusData));
                foreach ($skusData as $skus) {
                    $skus = $skus['skus'];
                    $i++;
                    // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品数据skus' . json_encode($skus).'第' . $i . '条');
                }

                //先把spu落到临时表
                $this->spuExSkus($skusData, $skusList);
                if ($spage == 1) {
                    array_first($skusList, function ($v) {
                        $sql_columns = 'tb_sku_id BIGINT NOT NULL AUTO_INCREMENT, ';
                        foreach ($v as $columns => $val) {
                            $sql_columns .= $columns . ' VARCHAR(255) DEFAULT \'\', ';
                        }
                        $sql_columns .= 'handle_status VARCHAR(50) DEFAULT \'PENDING\', PRIMARY KEY ( tb_sku_id ), ';
                        $sql_columns .= 'INDEX idx_sku_id (sku_id), INDEX idx_tb_sku_id (tb_sku_id)';
                        $sql = 'CREATE TABLE IF NOT EXISTS ' . $this->skusTable . ' (' . $sql_columns . ')ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
                        $this->connect->exec($sql);
                    });
                }

                $skuid   = array_column($skusList, 'sku_id');
                if (!$skuid) {
                    $page++;
                    continue;
                }
                $skuid   = array_map(function($item) {
                    return "'" . $item . "'";
                }, $skuid);
                $skuid   = implode(',', $skuid);
                $skulList = $this->connect->createQueryBuilder()
                    ->select('tb_sku_id,sku_id')
                    ->from($this->skusTable)
                    ->andWhere('sku_id IN(' . $skuid . ')')
                    ->execute()
                    ->fetchAll();
                $skulList = array_column($skulList, null, 'sku_id');
                app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品数据skulList' . json_encode($skulList));
                $this->connect->beginTransaction();
                try {

                    foreach ($skusList as $k => $sku) {

                        array_walk_recursive($sku, function (&$vv) {
                            $vv = trim(trim($vv, chr(0xc2) . chr(0xa0)));
                        });
                        if (isset($skulList[$sku['sku_id']])) {
                            $this->connect->update($this->skusTable, ['sku_id'=>$sku['sku_id']], ['handle_status' => 'PENDING']);
                        }else{
                            $this->connect->insert($this->skusTable, $sku);
                        }

                        if ($k % 10000 == 0) {
                            // 每1万条数据，提交一次插入记录
                            $this->connect->commit();
                            $this->connect->beginTransaction();
                        }
                    }
                    //更新tb_items表中的handle_status
                    $this->connect->update($this->table, ['handle_status' => 'SUCCESS'], ['iid' => $iids]);
                    $this->connect->commit();
                } catch (\Exception $e) {
                    $this->connect->rollback();
                    app('log')->debug(__CLASS__ . __FUNCTION__ . __LINE__ . $e->getFile() . $e->getLine() . $e->getMessage());
                }
                
            } catch (Exception $e) {
                app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品sku失败', [
                    'page' => $page,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
            $page++;
            $spage++;
            if ($page > 2) {
                $skus = [];
            }
        } while (!empty($skus)); 

        return $this;
    }

    //先落库sku
    function spuExSkus($skusData, &$skusList)
    {
        
        $skusList = [];
        $i = 0;
        //把淘宝spu转换成表格，如果有sku，则把sku转换成表格
        foreach($skusData as $skus){
            // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品数据cid' . $skus['cid'].'第' . $i . '条');
            if($skus['skus']){
                foreach($skus['skus']['sku'] as $sku){
                    $skusList[$i]['item_category'] = $skus['cid'];//商品主类目
                    $skusList[$i]['item_name'] = $skus['title'];//商品名称
                    $skusList[$i]['post_fee'] = $skus['post_fee'];//邮费
                    $skusList[$i]['approve_status'] = $skus['approve_status'];//审核状态
                    $skusList[$i]['num_iid'] = $skus['num_iid'];//商品ID
                    $skusList[$i]['goods_bn'] = $skus['outer_id'];//货品

                    $skusList[$i]['item_bn'] = $sku['outer_id'];//商品编号
                    $skusList[$i]['price'] = $sku['price'];//价格
                    $skusList[$i]['store'] = $sku['quantity'];//库存
                    $skusList[$i]['sku_id'] = $sku['sku_id'];//sku_id
                    $skusList[$i]['properties'] = $sku['properties'];//属性
                    $skusList[$i]['properties_name'] = $sku['properties_name'];//属性名称
                    $skusList[$i]['created'] = $sku['created'];//创建时间
                    $skusList[$i]['modified'] = $sku['modified'];//修改时间
                    $i++;
                }
            }
        }

        return $this;
    }

    public function getItemsAttributes()
    {
        // 获取商品属性
        $page = 1;
        $pageSize = 20;
        $total = 0;
        $itemsAttributes = [];
        $i = 0;

        do {
            try {
                $itemsAttributesObj = $this->connect->createQueryBuilder()
                    ->select('properties_name,sku_id,item_bn')
                    ->from($this->skusTable)
                    ->andWhere('handle_status = \'PENDING\'')
                    ->setMaxResults($pageSize)
                    ->setFirstResult(($page - 1) * $pageSize)
                    ->execute()
                    ->fetchAll();
                
                if(empty($itemsAttributesObj)){
                    break;
                }

                $itemsAttributes = array_column($itemsAttributesObj, 'properties_name');
                $itemsAttributes = array_unique($itemsAttributes);
                //bn与properties_name关联
                $itemBnPropertiesArr = [];
                foreach($itemsAttributesObj as $item){
                    $itemBnPropertiesArr[$item['item_bn']] = $item['properties_name'];
                }

                // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品属性itemBnPropertiesArr' . json_encode($itemBnPropertiesArr));
                // 解析属性字符串 1627207:242178561:颜色分类:淡雅米;20509:28317:尺码:XL
                foreach($itemsAttributes as $item){
                    $attributes = explode(';', $item);
                    $i++;
                    //$itemBnPropertiesArr 的结构是 ['item_bn' => '属性名称']
                    $itemBn = array_keys($itemBnPropertiesArr, $item);
                    $itemBn = array_shift($itemBn);
                    // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品属性itemBn' . $itemBn.'第' . $i . '条');

                    foreach ($attributes as $attribute) {
                        $parts = explode(':', $attribute);
                        
                        if (count($parts) >= 4) {
                            $attributeCode = $parts[0]; // 父级属性ID
                            $valueCode = $parts[1];     // 子级属性ID
                            $attributeName = $parts[2]; // 属性名称
                            $attributeValue = $parts[3]; // 属性值
                            
                            // 处理属性
                            $attributeId = $this->processAttribute($attributeCode, $attributeName, 'item_spec');
                            // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品属性' . json_encode($attributeId));
                            // 处理属性值
                            $attributeValueId = $this->processAttributeValue($attributeId, $valueCode, $attributeValue);
                                        
                            // 关联商品和属性  -- item_bn
                            $this->itemAttributeArr[$itemBn][] = [
                                'attribute_id' => $attributeId,
                                'attribute_value_id' => $attributeValueId,
                                'value_code' => $valueCode
                            ];
                            // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品属性itemAttributeArr' . json_encode($this->itemAttributeArr));
                        }
                    }
                }
                
            } catch (Exception $e) {
                app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品属性失败', [
                    'page' => $page,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
            $page++;
            // $itemsAttributes = [];//todo
        } while (!empty($itemsAttributes)); 
        return $this;
    }


    function getItemsCategory()
    {
        return $this;
        // 获取商品分类
        $page = 1;
        $pageSize = 1;
        $total = 0;
        $itemsCategory = [];
        $i = 0;
        $itemsCategoryService = new ItemsCategoryService;

        do {
            try {
                
                // 获取分类信息 
                $itemsObj = $this->tbClient->getTbItemsCategoryListing([], $page, $pageSize);
                $itemsCategory = json_decode($itemsObj, true);
                if ($itemsCategory['rsp'] !== 'succ') {
                    throw new \Exception('淘宝端无商品数据，或接口异常，请联系管理员！');
                }

                app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品分类' . json_encode($itemsCategory));

                $itemsData = $itemsCategory['data'] ?? [];
                if (!$itemsData) {
                    break; // 没有更多数据，退出循环    
                }
                $categoryListData = $itemsCategoryService->lists(['company_id' => $this->companyId, 'is_main_category' => 0], [], -1, 1, '*') ?? [];

                $categoryList = array_column($categoryListData['list'], null, 'category_id_taobao') ?? [];
                // 淘宝分类格式转换为分类数据格式   
                $transformedCategories = $this->transformCategoryData($itemsData, $categoryList, 0);
                // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '转换后的分类数据' . json_encode($transformedCategories));
                $treeCategory = make_tree_category($transformedCategories, 'category_id_taobao', 'parent_id_taobao', 'children');

                $itemsCategoryService->saveItemsCategory($treeCategory, $this->companyId, 0);
                
            } catch (Exception $e) {
                app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品分类失败', [
                    'page' => $page,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
            $page++;
            $itemsCategory = [];//todo
        } while (!empty($itemsCategory)); 
        return $this;
    }

    //获取商品基础数据
    public function getItemsBaseData()
    {
        $this->brandId = (new ItemsAttributesService())->getInfo([
            'company_id'     => $this->companyId,
            'attribute_type' => 'brand',
        ])['attribute_id'] ?? 0;

        //无品牌则新创建
        if(!$this->brandId){
            $this->brandId = (new ItemsAttributesService())->create([
                'company_id'     => $this->companyId,
                'attribute_type' => 'brand',
                'attribute_name' => 'AMANDAX',
                'attribute_sort' => 0,
                'is_show'        => 1,
                'is_image'       => 0,
                'distributor_id' => 0,
                'created'        => time(),
                'updated'        => time(),
            ]);
        }

        $this->mainCategoryId = (new ItemsCategoryService())->getInfo([
            'company_id'       => $this->companyId,
            'category_level'   => 3,
            'is_main_category' => true,
        ])['category_id'] ?? 0; 
        // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品基础数据mainCategoryId' . $this->mainCategoryId);
        $this->templatesId = (new ShippingTemplatesService())->getList(['is_free' => 1], [], 1, 100)['list'][0]['template_id'] ?? 0;   
        if(!$this->templatesId){
            app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品基础数据templatesId' . $this->templatesId);
            $this->templatesId = (new ShippingTemplatesService())->createShippingTemplates([
                'company_id' => $this->companyId,
                'name' => '默认包邮',
                'is_free' => 1,
                'status' => 1,
                'valuation' => 1,
                'nopost_conf' => [],
                'distributor_id' => 0,
                'supplier_id' => 0,
                'create_time' => time(),
                'update_time' => time(),
            ]);
        }

        return $this;
    }

    public function syncItemsRelation()
    {
        $this->connect->beginTransaction();
        $pageSize = 10;

        try {
            // sku总数
            $qb = $this->connect->createQueryBuilder()
            ->select('count(*)')
            ->from($this->skusTable);
            
            $count = $qb->execute()->fetchColumn();
            // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定关系count' . $count);
            $pageCount = ceil($count / $pageSize);
            
            $allItemBns = [];
            for ($page = 1; $page <= $pageCount; $page++) {
                $skusList = $this->getSkusList('*', $page, $pageSize);
                // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定关系skusList' . json_encode($skusList));

                if (!$skusList) {
                    continue;
                }

                $this->batchHandle($skusList);

                // 商品货号
                $bns  = array_column($skusList, 'item_bn');
                $successLists = $this->getItemBnsByBns($bns);
                $itemsBns   = array_column($successLists, 'item_bn');
                $allItemBns  = array_merge($allItemBns, $itemsBns);
                // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定关系allItemBns' . json_encode($allItemBns));
                // 对写入成功的商品做关系
                if ($successLists) {
                    $this->handleItemsRel($successLists, $skusList);
                }
            }

            if ($allItemBns) {
                // 更新操作状态为 DONE
                $updateSql = 'UPDATE ' . $this->skusTable . ' SET handle_status = "DONE" WHERE item_bn IN (' . implode(',', array_map(function($bn) {
                    return "'" . $bn . "'";
                }, $allItemBns)) . ')'; //table
                // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定关系updateSql' . $updateSql);
                $this->connect->executeUpdate($updateSql);
            }
            $this->connect->commit();
        } catch (\Exception $e) {
            $this->connect->rollback(); 
            app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定关系失败', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
        return $this;
    }

    function getSkusList($select, $page, $pageSize)
    {
        return $this->connect->createQueryBuilder()
            ->select($select)
            ->from($this->skusTable)
            ->andWhere('handle_status = \'PENDING\'')
            ->setMaxResults($pageSize)
            ->setFirstResult(($page - 1) * $pageSize)
            ->orderBy('tb_sku_id', 'ASC')
            ->execute()
            ->fetchAll();
    }

    function batchHandle($skusList)
    {
        $itembns = array_column($skusList, 'item_bn');
        $itemList    = (new ItemsService)->getItemsLists([
            'company_id' => $this->companyId,
            'item_bn'    => $itembns,
        ], 'item_bn,item_id');
        $itemBnList = array_column($itemList, 'item_id', 'item_bn');
        // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定关系itemBnList' . json_encode($itemBnList));
        $this->connect->beginTransaction();
        try {
            // 'is_default' => '商品是否为默认商品','default_item_id' => '默认商品ID','goods_id' => '产品ID','nospec' => '商品是否为单规格','weight' => '商品重量',
            // 'sort' => '商品排序','templates_id' => '运费模板id','pics' => '图片(DC2Type:json_array)','pics_create_qrcode' => '图片是否生成小程序码(DC2Type:json_array)',
            // video_type' => '视频类型 local:本地视频 tencent:腾讯视频','videos' => '视频','video_pic_url' => '视频封面图','intro' => '图文详情','purchase_agreement' => '购买协议',
            // 'is_show_specimg' => '详情页是否显示规格图片','enable_agreement' => '开启购买协议','date_type' => '有效期的类型, DATE_TYPE_FIX_TIME_RANGE:指定日期范围内, DATE_TYPE_FIX_TERM:固定天数后',
            // 'begin_date' => '有效期开始时间','end_date' => '有效期结束时间','fixed_term' => '有效期的有效天数','brand_logo' => '品牌图片','is_point' => '是否积分兑换 true可以 false不可以',
            // 'point' => '积分个数','distributor_id' => '店铺id,为0时表示该商品为商城商品，否则为店铺自有商品','volume' => '商品体积',
            // 'item_source' => '商品来源:mall:主商城;distributor:店铺自有;openapi:开放接口;','brand_id' => '品牌id',
            // 'tax_rate' => '税率, 百分之～/100','crossborder_tax_rate' => '跨境税率，百分比，小数点2位',
            // 'profit_type' => '分润类型, 默认为0配置分润,1主类目分润,2商品指定分润(比例),3商品指定分润(金额)','origincountry_id' => '产地国id',
            // 'taxstrategy_id' => '税费策略id','taxation_num' => '计税单位份数','profit_fee' => '分润金额,单位为分 冗余字段',
            // 'type' => '商品类型，0普通，1跨境商品，可扩展','is_profit' => '是否支持分润','created' => '创建时间','updated' => '更新时间',
            // 'is_gift' => '是否为赠品','is_package' => '是否为打包产品','tdk_content' => 'tdk详情','is_epidemic' => '是否为疫情需要登记的商品  1:是 0:否',
            // 'regions' => '产地地区','supplier_id' => '供应商id','is_market' => '供应商控制商品是否可售，0不可售，1可售','goods_bn' => '产品编号','supplier_goods_bn' => '供应商货号',
            // 'audit_date' => '商品审核时间','is_medicine' => '是否为药品，0否 1是','is_prescription' => '是否为处方药，0否 1是','supplier_item_id' => '供应商商品id',

            //skuslist 的结构 `tb_sku_id` bigint(20) NOT NULL AUTO_INCREMENT,
            // `item_category` varchar(255) DEFAULT '',
            // `item_name` varchar(255) DEFAULT '',
            // `post_fee` varchar(255) DEFAULT '',
            // `approve_status` varchar(255) DEFAULT '',
            // `num_iid` varchar(255) DEFAULT '',
            // `item_bn` varchar(255) DEFAULT '',
            // `price` varchar(255) DEFAULT '',
            // `store` varchar(255) DEFAULT '',
            // `sku_id` varchar(255) DEFAULT '',
            // `properties` varchar(255) DEFAULT '',
            // `properties_name` varchar(255) DEFAULT '',
            // `created` varchar(255) DEFAULT '',
            // `modified` varchar(255) DEFAULT '',
            foreach ($skusList as $k => $v) {

                $price = bcmul($v['price'], 100);
                $data  = [
                    'company_id'                 => $this->companyId,
                    'item_bn'                    => $v['item_bn'],
                    'item_type'                  => 'normal',
                    'item_category'              => $this->mainCategoryId,
                    'store'                      => $v['store'],
                    'item_name'                  => $v['item_name'],
                    'barcode'                    => $v['num_iid'],
                    'brief'                      => '', 
                    'price'                      => $price,
                    'cost_price'                 => bcmul($v['price'], 100),
                    'approve_status'             => $v['approve_status'],
                    'market_price'               => $price,
                    'templates_id'               => $this->templatesId,
                    'brand_id'                   => $this->brandId,
                    'is_default'                 => 0,
                    'start_num'                  => 0,
                    'is_point'                   => 0,
                    'point'                      => 0,
                    'is_package'                 => 0,
                    'is_gift'                    => 0,
                    'is_show_specimg'            => 0,
                    'enable_agreement'           => 0,
                    'goods_bn'                   => $v['goods_bn'],
                    
                    'pics'                       => json_encode([$v['default_img_url']??[]]),
                    'intro'                      => '',
                    'purchase_agreement'         => '',
                    //todo
                    'item_unit'                  => '',
                    'nospec'                     => 'false',

                    
                    //默认
                    'distributor_id'             => 0,
                    'sort'                       => 0,
                    'updated'                    => time(),
                    'created'                    => time(),
                    'tax_rate'                   => 0,
                    'crossborder_tax_rate'       => 0,
                    'profit_type'                => 0,
                    'origincountry_id'           => 0,
                    'taxstrategy_id'            => 0,
                    'taxation_num'              => 0,
                    'video_type'                 => 'local'
                    
                    
                ];
                // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定关系data' . json_encode($data));
                if ($itemBnList[$v['item_bn']] ?? []) {
                    unset($data['item_bn']);
                    // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定关系update' . $v['item_bn']);

                    $this->connect->update('items', $data, ['item_bn'=> $v['item_bn']]);
                }else{
                    // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定关系insert' . $v['item_bn']);

                    $this->connect->insert('items', $data);
                    $itemId = $this->connect->lastInsertId();
                    // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定关系itemId' . $itemId);
                }

                if ($k % 5000 == 0) {
                    // 每1万条数据，提交一次插入记录
                    $this->connect->commit();
                    $this->connect->beginTransaction();
                }
            }
            //循环结束还没提交事务，则提交事务
            if ($this->connect->isTransactionActive()) {
                $this->connect->commit();
            }
        } catch (\Exception $e) {
            $this->connect->rollback();
            $msg = __CLASS__ . __FUNCTION__ . __LINE__ . $e->getFile() . $e->getLine() . $e->getMessage();
            app('log')->debug($msg);
            throw new \Exception($msg);
        }
    }

    function handleItemsRel($successLists, $skusList)
    {
        // 商品-属性-属性值关系
        $this->handleItemsAttributeRel($successLists, $skusList);

        // 设置默认主商品
        $defaultItemsList = $this->setDefaultItems($successLists);

        // 主商品 - 分类关系
        $this->handleDefaultItemsCategoryRel($defaultItemsList);

        // 商品-店铺关系
        $this->handleItemsShopRel($defaultItemsList);
        
    }

    /**
     * 关联商品和属性
     */
    protected function handleItemsAttributeRel($successLists, $skusList) 
    {
        // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定111关系itemAttributeArr' . json_encode($this->itemAttributeArr));

        //{"P06401==2":[{"attribute_id":"1","attribute_value_id":"8","value_code":"22788439571"},{"attribute_id":"2","attribute_value_id":"9","value_code":"28313"}],"P06402==2":[{"attribute_id":"1","attribute_value_id":"8","value_code":"22788439571"},{"attribute_id":"2","attribute_value_id":"10","value_code":"28314"}],"P06403==2":[{"attribute_id":"1","attribute_value_id":"8","value_code":"22788439571"},{"attribute_id":"2","attribute_value_id":"11","value_code":"28315"}],"P06404==2":[{"attribute_id":"1","attribute_value_id":"8","value_code":"22788439571"},{"attribute_id":"2","attribute_value_id":"12","value_code":"28316"}],"P06405==2":[{"attribute_id":"1","attribute_value_id":"8","value_code":"22788439571"},{"attribute_id":"2","attribute_value_id":"13","value_code":"28317"}],"Y61201==2":[{"attribute_id":"1","attribute_value_id":"14","value_code":"4266701"},{"attribute_id":"2","attribute_value_id":"2","value_code":"309165683"}],"Y61202==2":[{"attribute_id":"1","attribute_value_id":"14","value_code":"4266701"},{"attribute_id":"2","attribute_value_id":"3","value_code":"888022022"}],"Y61203==2":[{"attribute_id":"1","attribute_value_id":"14","value_code":"4266701"},{"attribute_id":"2","attribute_value_id":"4","value_code":"888022023"}],"Y61204==2":[{"attribute_id":"1","attribute_value_id":"14","value_code":"4266701"},{"attribute_id":"2","attribute_value_id":"5","value_code":"1546621474"}],"Y61205==2":[{"attribute_id":"1","attribute_value_id":"14","value_code":"4266701"},{"attribute_id":"2","attribute_value_id":"6","value_code":"1616319389"}],"Y61201==25":[{"attribute_id":"1","attribute_value_id":"14","value_code":"4266701"},{"attribute_id":"2","attribute_value_id":"9","value_code":"28313"}],"Y61202==25":[{"attribute_id":"1","attribute_value_id":"14","value_code":"4266701"},{"attribute_id":"2","attribute_value_id":"10","value_code":"28314"}],"Y61203==25":[{"attribute_id":"1","attribute_value_id":"14","value_code":"4266701"},{"attribute_id":"2","attribute_value_id":"11","value_code":"28315"}],"Y61204==25":[{"attribute_id":"1","attribute_value_id":"14","value_code":"4266701"},{"attribute_id":"2","attribute_value_id":"12","value_code":"28316"}],"Y61205==25":[{"attribute_id":"1","attribute_value_id":"14","value_code":"4266701"},{"attribute_id":"2","attribute_value_id":"13","value_code":"28317"}],"Y61206==2":[{"attribute_id":"1","attribute_value_id":"15","value_code":"3312588"},{"attribute_id":"2","attribute_value_id":"2","value_code":"309165683"}],"Y61207==2":[{"attribute_id":"1","attribute_value_id":"15","value_code":"3312588"},{"attribute_id":"2","attribute_value_id":"3","value_code":"888022022"}],"Y61208==2":[{"attribute_id":"1","attribute_value_id":"15","value_code":"3312588"},{"attribute_id":"2","attribute_value_id":"4","value_code":"888022023"}],"Y61209==2":[{"attribute_id":"1","attribute_value_id":"15","value_code":"3312588"},{"attribute_id":"2","attribute_value_id":"5","value_code":"1546621474"}],"Y61210==2":[{"attribute_id":"1","attribute_value_id":"15","value_code":"3312588"},{"attribute_id":"2","attribute_value_id":"6","value_code":"1616319389"}]}

        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        //$itemId, $attributeId, $attributeValueId, $valueCode
        foreach ($successLists as $k => $v) {
            // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定itemBn' . $v['item_bn']);
            // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定itemBn' . json_encode($this->itemAttributeArr[$v['item_bn']]??[]) );
            //取properties
            $properties = $this->itemAttributeArr[$v['item_bn']] ?? [];
            // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定properties' . json_encode($properties));
            foreach($properties as $property){
                $attributeId = $property['attribute_id'];
                $attributeValueId = $property['attribute_value_id'];
                $valueCode = $property['value_code'];
                // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定attributeId' . $attributeId);
                // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定attributeValueId' . $attributeValueId);
                // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定valueCode' . $valueCode);
                // 查找是否已存在关联
                // $existingLink = $qb->select('id')
                //     ->from('items_rel_attributes')
                //     ->andWhere($qb->expr()->eq('item_id', $v['item_id']))
                //     ->andWhere($qb->expr()->eq('attribute_id', $attributeId))
                //     ->andWhere($qb->expr()->eq('attribute_value_id', $attributeValueId))
                //     ->execute()
                //     ->fetch();
                $existingLink = DB::table('items_rel_attributes')
                    ->select('id')
                    ->where('item_id', $v['item_id'])
                    ->where('attribute_id', $attributeId)
                    ->where('attribute_value_id', $attributeValueId)
                    ->first();
                
                // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . 'existingLink' . json_encode($existingLink));
                if (!$existingLink) {
                    // 插入关联关系
                    $this->connect->insert('items_rel_attributes', [
                        'item_id' => $v['item_id'],
                        'company_id' => $this->companyId,
                        'attribute_type' => 'item_spec',
                        'attribute_id' => $attributeId,
                        'attribute_value_id' => $attributeValueId,
                        'custom_attribute_value' => $valueCode
                    ]);
                    $this->connect->lastInsertId();
                }
            }
        }
        return true;
    }

    function setDefaultItems($successLists)
    {
        $defaultItem  = array_column($successLists, null, 'goods_bn');
        $defaultItem = array_unique($defaultItem, SORT_REGULAR);
        // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定defaultItem' . json_encode($defaultItem));
        $itemsService = new ItemsService();
        foreach ($defaultItem as $itemGoodsBn => $itemInfo) {
            
            $defaultItemId  = $itemInfo['item_id'];
            $defaultItemArr = [
                'default_item_id' => $defaultItemId,
                'goods_id'        => $defaultItemId,
            ];
            $itemsService->simpleUpdateBy([
                'goods_bn' => $itemGoodsBn,
            ], [
                'is_default' => 0,
            ] + $defaultItemArr);

            $itemsService->simpleUpdateBy([
                'item_id' => $defaultItemId,
            ], [
                'is_default' => 1,
            ] + $defaultItemArr);
        }

        return $defaultItem;
    }

    function handleDefaultItemsCategoryRel($defaultItemsList)
    {
        $itemsRelCatsService = new ItemsRelCatsService();
        $itemsRelCatsList    = array_column($defaultItemsList, null, 'item_id');
        // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定itemsRelCatsList' . json_encode($itemsRelCatsList));    
        $itemIds             = array_keys($itemsRelCatsList);
        if ($itemIds) {
            $itemsRelCatList = $itemsRelCatsService->getList([
                'company_id' => $this->companyId,
                'item_id'    => $itemIds,
            ]);
            $itemsRelCatList = array_column($itemsRelCatList, null, 'item_id');
        }
        
        $exist = [];
        // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定itemsRelCatsList' . json_encode($itemsRelCatsList));
        foreach ($itemsRelCatsList as $itemId => $itemRel) {
            //已处理过itemGoodsBn跳过
            if(in_array($itemRel['goods_bn'], $exist)){
                // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定' . $itemRel['goods_bn'] . '已处理过');
                continue;
            }
            // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定itemGoodsBn' . $itemRel['goods_bn']);
            $filter = [
                'company_id' => $this->companyId,
                'item_id'    => $itemRel['item_id'],
            ];
            if (!isset($itemRel['item_category'])) {
                continue;
            }

            $data = [
                'category_id' => $itemRel['item_category'],
            ];
            if ($itemsRelCatList[$itemRel['item_id']] ?? 0) {
                $itemsRelCatsService->updateOneBy($filter, $data);
            } else {
                $itemsRelCatsService->create($filter + $data);
            }
            array_push($exist, $itemRel['goods_bn']);
            // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定end'.json_encode($exist));
        }
    }

    //商品-店铺关系
    // shop_cids : 1818786075,1572077250,1437220144,1535966807,1523403545 
    function handleItemsShopRel($defaultItemsList)
    {
        // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定defaultItemsList' . json_encode($defaultItemsList));
        $itemsShopRelService = new ItemsRelCatsService();
        $itemsShopsRelList    = array_column($defaultItemsList, null, 'item_id');
        $itemIds             = array_keys($itemsShopsRelList);
        if ($itemIds) {
            $itemsShopRelList = $itemsShopRelService->getList([
                'company_id' => $this->companyId,
                'item_id'    => $itemIds,
            ]);
            $itemsShopRelList = array_column($itemsShopRelList, null, 'item_id');
        }

        //tb_items表中shop_cids outer_id => goods_bn
        $goodsBns = array_column($itemsShopsRelList, 'goods_bn'); //处理成字符串
        $goodsBns = array_map(function($goodsBn){
            return "'" . $goodsBn . "'";
        }, $goodsBns);
        $tbItems = $this->connect->createQueryBuilder()
            ->select('shop_cids,outer_id')
            ->from('tb_items')
            ->andWhere('outer_id IN (' . implode(',', $goodsBns) . ')')
            ->execute()
            ->fetchAll();
        $tbItems = array_column($tbItems, null, 'outer_id');

        //items_category 通过 shop_cids拿到 category_id
        $itemsCategoryService = new ItemsCategoryService();
        $shopCids =  array_column($tbItems, 'shop_cids');//shop_cids中以逗号分割 转换成一维
        $shopCids = array_map(function($shopCids){
            return explode(',', $shopCids);
        }, $shopCids);
        $shopCids = array_unique(array_merge(...$shopCids));
        // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定shopCids' . json_encode($shopCids));


        $itemsCategoryList = $itemsCategoryService->lists([
            'company_id' => $this->companyId,
            'category_id_taobao'    => $shopCids,
        ], [], -1, 1, '*');

        $itemsCategoryList = array_column($itemsCategoryList['list'], null, 'category_id_taobao');
        // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定itemsCategoryList' . json_encode($itemsCategoryList));

        // 批量更新items_rel_cats表
        $batchData = [];
        $itemsCatShopRelList = array_column($itemsShopRelList, null, 'category_id');
        // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定itemsShopsRelList' . json_encode($itemsShopsRelList));
        foreach ($itemsShopsRelList as $itemId => $itemShopRel) {
            $goodsBn = $itemShopRel['goods_bn'];
            // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定goodsBn' . $goodsBn);
            if (isset($tbItems[$goodsBn])) {
                $shopCids = $tbItems[$goodsBn]['shop_cids'];
                $shopCidArray = explode(',', $shopCids);
                // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定shopCidArray' . json_encode($shopCidArray));
                foreach ($shopCidArray as $shopCid) {
                    $shopCid = trim($shopCid);
                    // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定shopCid' . $shopCid);
                    if (isset($itemsCategoryList[$shopCid]) ) {
                        $categoryId = $itemsCategoryList[$shopCid]['category_id'];
                        if(!isset($itemsCatShopRelList[$categoryId])){
                            $batchData[] = [
                                'company_id' => $this->companyId,
                                'item_id' => $itemId,
                                'category_id' => $categoryId,
                                'created' => time(),
                                'updated' => time()
                            ];
                        }
                    }
                }
            }
        }
        // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品绑定batchData' . json_encode($batchData));
        // 批量插入或更新
        if (!empty($batchData)) {
            try {
                $this->connect->beginTransaction();
               
                // 批量插入新的关联关系
                foreach($batchData as $idata){
                    $this->connect->insert('goods_items_rel_cats', $idata);
                }
                
                $this->connect->commit();
                
                app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '批量更新items_rel_cats成功，共处理' . count($batchData) . '条记录');
                
            } catch (Exception $e) {
                $this->connect->rollback();
                app('log')->error(__CLASS__ . __FUNCTION__ . __LINE__ . '批量更新items_rel_cats失败: ' . $e->getMessage());
                throw $e;
            }
        }


    }
    

    private function getItemBnsByBns($bns)
    {
        $qb = $this->connect->createQueryBuilder();

        $bns = array_map(function($bn) {
            return "'" . $bn . "'";
        }, $bns);

        return $qb->select('item_id,item_bn,item_category,is_default,goods_bn')
            ->from('items')
            ->andWhere('item_bn IN (' . implode(',', $bns) . ')')
            ->addOrderBy('item_id', 'ASC')
            ->addOrderBy('is_default', 'DESC')
            ->execute()
            ->fetchAll();
    }

    /**
     * 填充基础数据
     * 商品图片
     * 规格图片
     * @return array
     */
    function fillItemsBaseData($isFill = false)
    {
        $page = 1;
        $spage = 1;
        $pageSize = 1;
        $total = 0;
        $items = [];
        $i = 0;

        do {
            $this->connect->beginTransaction();
            try {
                //如果isFill为true，则只填充fillIids的数据
                if($isFill){
                    $iids = array_unique($this->fillIids) ?? [];
                }else{
                    // 获取spu tb_items表
                    $spuObj = $this->connect->createQueryBuilder()
                        ->select('tb_iid,iid,outer_id')
                    ->from($this->table)
                    // ->andWhere('handle_status = \'PENDING\'')
                    ->setMaxResults($pageSize)
                    ->setFirstResult(($page - 1) * $pageSize)
                    ->execute()     
                    ->fetchAll();
                    $iids = array_column($spuObj, 'iid');
                    $iids = array_unique($iids);
                }
                if (!$iids) {
                    break; // 没有更多数据，退出循环 
                }
                // $iids = ['691447820988'];
                $itemsData = [];
                $specs = [];
                foreach($iids as $iid){
                    $itemsObj = $this->tbClient->getFillTbItemsInfo(['iid'=>$iid], $page, $pageSize);
                    $items = json_decode($itemsObj, true);
                    if ($items['rsp'] !== 'succ') {
                        continue;
                    }
                    // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品fillItemsBaseData' . json_encode($items['data']));
                    $outer_id = $items['data']['outer_id'];
                    //图片
                    // {
                    //     "position": "",
                    //     "image_id": 1179326698392,
                    //     "thumbnail_url": "https://img.alicdn.com/bao/uploaded/i3/409207888/O1CN01xTUpsm288kxPDIpJk_!!409207888.jpg",
                    //     "created": ""
                    //   }
                    //批量图片
                    $itemsData[$outer_id]['pics'] = json_encode(array_column($items['data']['item_imgs']['item_img'], 'thumbnail_url'));
                    //详情
                    $itemsData[$outer_id]['intro'] = $items['data']['description'];
                    //规格图片
                    // "prop_imgs": {
                    //     "prop_img": [
                    //     {
                    //         "url": "https://img.alicdn.com/bao/uploaded/i4/409207888/O1CN01voWWNc288kxTYaovP_!!409207888.jpg",
                    //         "image_id": 1177753625265,
                    //         "properties ": "1627207:22788439571"
                    //     }
                    //     ]
                    // }
                    //更新items_attribute_values表中的image_url ，properties中分号分割，前为attribute_id，后为attribute_value_id 先存到数组中
                    foreach($items['data']['prop_imgs']['prop_img'] as $prop){
                        app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品fillItemsBaseDataprop' . json_encode($prop));
                        $properties = explode(':', $prop['properties ']);
                        $attributeValueId = $properties[1];
                        $specs[] = [
                            'oms_value_id' => $attributeValueId,
                            'image_url' => $prop['url']
                        ];
                    }
                    // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品fillItemsBaseData' . json_encode($items));
                    
                    //sku中的props_name
                    // "skus": {
                    //     "sku": [
                    //     {
                    //         "properties_name": "1627207:22788439571:颜色分类:午夜迷蓝;20509:28313:尺码:XS",
                    //     }
                    $allSkuProps = [];
                    foreach($items['data']['skus']['sku'] as $sku){
                        $allSkuProps = array_merge(
                            $allSkuProps, 
                            explode(';', $sku['properties_name'])
                        );
                    }
                    $itemsProps = explode(';', $items['data']['props_name']);
                    //去掉itemsProps中skuProps中存在的
                    $itemsProps = array_diff($itemsProps, $allSkuProps);

                    foreach ($itemsProps as $attribute) {
                        $parts = explode(':', $attribute);
                        
                        if (count($parts) >= 4) {
                            $attributeCode = $parts[0]; // 父级属性ID
                            $valueCode = $parts[1];     // 子级属性ID
                            $attributeName = $parts[2]; // 属性名称
                            $attributeValue = $parts[3]; // 属性值
                            
                            // 处理属性
                            $attributeId = $this->processAttribute($attributeCode, $attributeName, 'item_params');
                            // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品属性' . json_encode($attributeId));
                            // 处理属性值
                            $attributeValueId = $this->processAttributeValue($attributeId, $valueCode, $attributeValue);
                                        
                            // 关联商品和属性  -- item_bn
                            $itemsData[$outer_id]['attributes'][] = [
                                'attribute_id' => $attributeId,
                                'attribute_value_id' => $attributeValueId,
                                'value_code' => $valueCode
                            ];
                        }
                    }
                    
                }

                //通过outer_id 获取items表中的item_id
                foreach($itemsData as $outer_id => $itemData){
                    $qb = $this->connect->createQueryBuilder();
                    $itemList = $qb->select('item_id,goods_bn')
                        ->from('items')
                        ->andWhere($qb->expr()->eq('goods_bn', $qb->expr()->literal($outer_id)))
                        ->execute()
                        ->fetchAll();

                    foreach($itemList as $item){
                        // $qb1 = $this->connect->createQueryBuilder();
                        foreach($itemData['attributes'] as $attribute){
                            //存在则更新，不存在则插入
                            // $existingLink = $qb1->select('id')
                            //     ->from('items_rel_attributes')
                            //     ->andWhere($qb1->expr()->eq('item_id', $item['item_id']))
                            //     ->andWhere($qb1->expr()->eq('attribute_id', $attribute['attribute_id']))
                            //     ->andWhere($qb1->expr()->eq('attribute_value_id', $attribute['attribute_value_id']))
                            //     ->execute()
                            //     ->fetch();
                            $existingLink = DB::table('items_rel_attributes')
                                ->select('id')
                                ->where('item_id', $item['item_id'])
                                ->where('attribute_id', $attribute['attribute_id'])
                                ->where('attribute_value_id', $attribute['attribute_value_id'])
                                ->first();
                            if(!$existingLink){
                                $this->connect->insert('items_rel_attributes', [
                                    'item_id' => $item['item_id'],
                                    'company_id' => $this->companyId,
                                    'attribute_type' => 'item_params',
                                    'attribute_id' => $attribute['attribute_id'],
                                    'attribute_value_id' => $attribute['attribute_value_id'],
                                    'custom_attribute_value' => $attribute['value_code']
                                ]); 
                            }
                        }
                    }

                }
                
                //更新items表中的pics和intro
                foreach($itemsData as $outer_id => $itemData){
                    $this->connect->update('items', [
                        'pics' => $itemData['pics'],
                        'intro' => $itemData['intro']
                    ], [
                        'goods_bn' => $outer_id
                    ]);
                }
                //批量更新items_attribute_values表中的image_url
                foreach($specs as $spec){
                    $this->connect->update('items_attribute_values', [
                        'image_url' => $spec['image_url']
                    ], [
                        'oms_value_id' => $spec['oms_value_id']
                    ]);
                }

                $this->connect->commit();
            } catch (Exception $e) {
                $this->connect->rollback();
                app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品失败', [
                    'page' => $page,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
            $page++;
            $spage++;
            if ($page > 2) {
                $items = [];
            }
        } while (!empty($items)); 
        return $this;
    }

    /**
     * 同步淘宝商品 -- 单个
     * 销售分类、管理分类、规格、参数、图片、描写、sku
     * 更新数据表，items，items_attributes，items_attribute_values，items_rel_attributes，items_rel_cats
     */
    function syncSingleTbItems($iid, $categoryId) {
        $this->connect->beginTransaction();
        try {
            $itemsObj = $this->tbClient->getFillTbItemsInfo(['iid'=>$iid]);
            $items = json_decode($itemsObj, true);
            if ($items['rsp'] !== 'succ') {
                throw new Exception('同步淘宝商品失败');
            }
            //spu goods_bn
            $outer_id = $items['data']['outer_id'];
            if($this->isExistItems($outer_id)){
                throw new Exception('商品已存在');
            }

            //基础数据
            $this->getItemsBaseData();

            //插入items表 -- 组入参数
            $defaultItemId = $this->insertItems($items['data'], $categoryId, $relItemAttributes);
            //关联规格
            $this->insertItemsRelAttributes($relItemAttributes);

            //销售分类
            $this->insertItemsRelCats($items['data']['shopcat_id'], $defaultItemId);
            //处理图片转换 -- todo
            // $this->convertImages($items['data']);

            $this->connect->commit();
        } catch (Exception $e) {
            $this->connect->rollback();
            throw $e;
        }

    }

    private function isExistItems($outer_id){
        $qb = $this->connect->createQueryBuilder();
        $item = $qb->select('item_id')
            ->from('items')
            ->andWhere($qb->expr()->eq('goods_bn', $qb->expr()->literal($outer_id)))
            ->execute()
            ->fetch();
        if($item){
            return true;
        }
        return false;
    }

    /**
     * 单个商品
     * 插入items表、规格、参数
     */
    private function insertItems($itemsData, $categoryId, &$relItemAttributes){

        $nospec = count($itemsData['skus']['sku']) > 1 ? 'true' : 'false';
        $allSkuProps = [];
        $itemIds = [];
        $defaultItemId = 0;
        // $conn = app('registry')->getConnection('default');
        // $qb = $conn->createQueryBuilder();
        foreach ($itemsData['skus']['sku'] as $k => $v) {
            $price = bcmul($v['price'], 100);
            $data  = [
                'company_id'                 => $this->companyId,
                'item_bn'                    => $v['outer_id'],
                'item_type'                  => 'normal',
                'item_category'              => $categoryId,
                'store'                      => $v['quantity'],
                'item_name'                  => $itemsData['title'],
                'barcode'                    => $itemsData['iid'], 
                'brief'                      => '', 
                'price'                      => $price,
                'cost_price'                 => bcmul($v['price'], 100),
                'approve_status'             => $itemsData['status'],
                'market_price'               => $price,
                'templates_id'               => $this->templatesId ?? 1,
                'brand_id'                   => $this->brandId ?? 1,
                'is_default'                 => 0,
                'start_num'                  => 0,
                'is_point'                   => 0,
                'point'                      => 0,
                'is_package'                 => 0,
                'is_gift'                    => 0,
                'is_show_specimg'            => 0,
                'enable_agreement'           => 0,
                'goods_bn'                   => $itemsData['outer_id'],
                
                'pics'                       => json_encode(array_column($itemsData['item_imgs']['item_img'], 'thumbnail_url')),
                'intro'                      => $itemsData['description'],
                'purchase_agreement'         => '',
                //todo
                'item_unit'                  => '',
                'nospec'                     => $nospec,

                //默认
                'distributor_id'             => 0,
                'sort'                       => 0,
                'updated'                    => time(),
                'created'                    => time(),
                'tax_rate'                   => 0,
                'crossborder_tax_rate'       => 0,
                'profit_type'                => 0,
                'origincountry_id'           => 0,
                'taxstrategy_id'            => 0,
                'taxation_num'              => 0,
                'video_type'                 => 'local'        
            ];

            //参数
            $allSkuProps = array_merge(
                $allSkuProps, 
                explode(';', $v['properties_name'])
            );
            if($k == 0){
                $data['is_default'] = 1;
            }
            $this->connect->insert('items', $data);
            if($k == 0){
                $defaultItemId = $this->connect->lastInsertId();
            }
            $itemIds[] = $this->connect->lastInsertId();
        }

        //is_default,goods_id, default_item_id -- 更新
        if($defaultItemId){
            $this->connect->update('items', [
                'goods_id' => $defaultItemId,
                'default_item_id' => $defaultItemId
            ], [
                'goods_bn' => $itemsData['outer_id']
            ]);
        }

        //规格图片
        $specs = [];
        foreach($itemsData['prop_imgs']['prop_img'] as $prop){
            $properties = explode(':', $prop['properties ']);
            $specCode = $properties[1];
            $specs[$specCode] = $prop['url'];
        }

        //参数、规格图片
        $relAttributes = [];
        $itemsProps = explode(';', $itemsData['props_name']);
        foreach ($itemsProps as $attribute) {
            //itemsProps中skuProps中存在的则 item_params -> item_spec
            if(in_array($attribute, $allSkuProps)){
                $attributeType = 'item_spec';
            }else{
                $attributeType = 'item_params';
            }

            $parts = explode(':', $attribute);
            if (count($parts) >= 4) {
                $attributeCode = $parts[0]; // 父级属性ID
                $valueCode = $parts[1];     // 子级属性ID
                $attributeName = $parts[2]; // 属性名称
                $attributeValue = $parts[3]; // 属性值
                
                // 处理属性
                $attributeId = $this->processAttribute($attributeCode, $attributeName, $attributeType);
                // 处理属性值
                $attributeValueId = $this->processAttributeValue($attributeId, $valueCode, $attributeValue, $specs[$valueCode] ?? '');
                            
                // 关联商品和属性  -- item_bn
                $relAttributes[] = [
                    'attribute_id' => $attributeId,
                    'attribute_value_id' => $attributeValueId,
                    'value_code' => $valueCode,
                    'attribute_type' => $attributeType
                ];
            }
        }

        //relAttributes中outer_id转换成item_id
        $relItemAttributes = [];
        foreach($itemIds as $itemId){
            foreach($relAttributes as $attribute){
                $relItemAttributes[$itemId][] = $attribute;
            }
        }

        return $defaultItemId;
    }

    function insertItemsRelAttributes($relAttributes){
        foreach($relAttributes as $itemId => $attributes){
            foreach($attributes as $attribute){
                $data = [
                    'item_id' => $itemId,
                    'company_id' => $this->companyId,
                    'attribute_sort' => 0,
                    'attribute_type' => $attribute['attribute_type'],
                    'attribute_id' => $attribute['attribute_id'],
                    'attribute_value_id' => $attribute['attribute_value_id'],
                    'custom_attribute_value' => $attribute['value_code'],
                ];
                $this->connect->insert('items_rel_attributes', $data);
            }
        }
        return true;
    }

    function insertItemsRelCats($shopcatId, $defaultItemId){
        //explode(',', $shopcatId)去空和去重
        $shopcatIdsArr = array_filter(explode(',', $shopcatId));
        $shopcatIdsArr = array_unique($shopcatIdsArr);
        $itemsCategoryService = new ItemsCategoryService();
        $itemsCategoryList = $itemsCategoryService->lists([
            'company_id' => $this->companyId,
            'category_id_taobao'    => $shopcatIdsArr,
        ], [], -1, 1, '*');

        $itemsCategoryList = array_column($itemsCategoryList['list'], null, 'category_id_taobao');

        // $shopcatIdsArr = explode(',', $shopcatId);
        foreach($shopcatIdsArr as $categoryId){
            //转换成category_id
            $categoryId = $itemsCategoryList[$categoryId]['category_id'];
            $this->connect->insert('goods_items_rel_cats', ['item_id' => $defaultItemId, 'company_id' => $this->companyId, 'category_id' => $categoryId, 'created' => time(), 'updated' => time()]);
        }
        return true;
    }
    //--------- 内部方法---------------
    /**
     * 处理属性，存在则更新，不存在则插入
     */
    protected function processAttribute($attributeCode, $attributeName, $attributeType = 'item_spec')
    {
        // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品属性attributeCode:' . $attributeCode);
        // 查找是否已存在该属性
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $existingAttribute = $qb->select('attribute_id')
            ->from('items_attributes')
            ->andWhere($qb->expr()->eq('attribute_code', $qb->expr()->literal($attributeCode)))
            ->execute()
            ->fetch();
       
        // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品属性existingAttribute:' . json_encode($existingAttribute));
        if ($existingAttribute) { 
            // 更新现有属性 attribute_type 为 item_spec，attribute_memo 备注attributeName，attribute_code 为 attributeCode
            // $this->connect->update('items_attributes', ['attribute_id'=>$existingAttribute['attribute_id']], [
            //     'attribute_memo' => $attributeName, 
            //     'attribute_name' => $attributeName,
            //     'attribute_code' => $attributeCode,
            //     'updated' => time()]);
            return $existingAttribute['attribute_id'];
        } else {
            // 插入新属性
            $attributeId = $this->connect->insert('items_attributes', [
                'attribute_type' => $attributeType,
                'attribute_code' => $attributeCode,
                'attribute_name' => $attributeName,
                'company_id' => $this->companyId,
                'attribute_sort' => 0,
                'is_show' => true,
                'is_image' => false,
                'created' => time(),
                'updated' => time()
            ]);
            
            return $this->connect->lastInsertId();
        }
        return $existingAttribute['attribute_id'];
    }

    /**
     * 处理属性值
     */
    protected function processAttributeValue($attributeId, $valueCode, $attributeValue, $imageUrl = null)
    {
        // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品属性valueCode:' . $valueCode);
        // 查找是否已存在该属性值
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $existingValue = $qb->select('attribute_value_id')
            ->from('items_attribute_values')
            ->andWhere($qb->expr()->eq('attribute_id', $attributeId))
            ->andWhere($qb->expr()->eq('oms_value_id', $valueCode))
            ->execute()
            ->fetch();
        // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品属性existingValue:' . json_encode($existingValue));
        if ($existingValue) {
            // 更新现有属性值
            // $this->connect->update('items_attribute_values', [
            //     'attribute_value_id'=>$existingValue['attribute_value_id']], [
            //         'attribuattribute_valuete_name' => $attributeValue, 
            //         'updated' => time()]);   
        } else {
            // 插入新属性值
            $this->connect->insert('items_attribute_values', [
                'attribute_id' => $attributeId,
                'oms_value_id' => $valueCode,
                'attribuattribute_valuete_name' => $attributeValue,
                'company_id' => $this->companyId,
                'sort' => 0,
                'shop_id' => 0,
                'created' => time(),
                'updated' => time(),
                'image_url' => $imageUrl
            ]);
            return $this->connect->lastInsertId();
        }
        return $existingValue['attribute_value_id'];
    }

}