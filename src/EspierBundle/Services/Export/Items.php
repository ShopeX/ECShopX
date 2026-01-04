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

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use GoodsBundle\Entities\ItemRelAttributes;
use GoodsBundle\Entities\ItemsAttributes;
use GoodsBundle\Entities\ItemsAttributeValues;
use GoodsBundle\Entities\ItemsProfit;
use GoodsBundle\Services\ItemsService;
use KaquanBundle\Entities\MemberCardGrade;
use KaquanBundle\Entities\VipGrade;
use KaquanBundle\Services\MemberCardService;
use KaquanBundle\Services\VipGradeService;
use OrdersBundle\Services\ShippingTemplatesService;
use GoodsBundle\Services\ItemsRelCatsService;
use GoodsBundle\Services\ItemsCategoryService;
use PromotionsBundle\Entities\MemberPrice;
use SupplierBundle\Services\SupplierItemsService;
use SupplierBundle\Services\SupplierService;

class Items implements ExportFileInterface
{
    public const MEMBER_PRICE_KEY = 'member_price';//忽略的字段
    private $title = [
        'item_main_category' => '管理分类',
        'item_name' => '商品名称',
        'goods_bn' => 'SPU编码',
        'item_bn' => 'SKU编码',
        // 'supplier_goods_bn' => '供应商商品货号',
        'supplier_name' => '供应商名称',
        'brief' => '简介',
        'price' => '销售价',
        'market_price' => '市场价',
        'cost_price' => '成本价',
        'start_num' => '起订量',
        'member_price' => '会员价', ##会被替换
        // 'gross_profit_rate' => '毛利率(%)', ##会被替换
        'store' => '库存',
        'pics' => '图片',
        'intro' => '详情图',//0815新增
        'spec_pics' => '规格图',//0815新增
        'videos' => '视频',
        'goods_brand' => '品牌',
        'templates_id' => '运费模板',
        'item_category' => '分类',
        'weight' => '重量',
        'barcode' => '条形码',
        'item_unit' => '单位',
        'attribute_name' => '规格值',
        'item_params' => '参数值',
        'delivery_time' => '发货时间', // 0818新增
        'is_profit' => '是否支持分润',
        'profit_type' => '分润类型',
        'profit' => '拉新分润',
        'popularize_profit' => '推广分润',
        'approve_status' => '商品状态',
        'is_market' => '供应状态',
        'audit_status' => '审核状态',
    ];

    public $operator_type = '';//区分供应商，商户，店铺，平台
    public $item_source = '';//区分供应商商品 supplier，平台自营商品 platform

    public function getFileName($filter)
    {
        $fileName = 'items' . date('_Ymd_') . mt_rand(1000, 9999);
        return $fileName;
    }

    public function getCount($filter)
    {
        $itemService = new ItemsService();
        $isGetSkuList = $filter['isGetSkuList'];
        unset($filter['isGetSkuList']);
        if (isset($filter['item_id'])) {
            $filter = [
                'company_id' => $filter['company_id'],
                'item_id' => $filter['item_id']
            ];
        }
        if (isset($filter['item_id']) && $filter['item_id']) {
            $filter['default_item_id'] = $filter['item_id'];
            unset($filter['item_id']);
        }
        unset($filter['operator_type']);
        unset($filter['item_source']);//item_source
        app('log')->info(__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));
        $count = $itemService->getSkuItemsList($filter, 1, 1)['total_count'];
        app('log')->info(__FUNCTION__.':'.__LINE__.':count:'.$count);
        return $count;
    }

    public function exportData($filter)
    {
        if(isset($filter['operator_type'])){
            $this->operator_type = $filter['operator_type'];
            unset($filter['operator_type']);
        }
        // if(isset($filter['operator_id'])){
        //     $this->operator_id = $filter['operator_id'];
        //     unset($filter['operator_id']);
        // }
        if(isset($filter['item_source'])){
            $this->item_source = $filter['item_source'];
            unset($filter['item_source']);
        }        
        if($this->operator_type == 'supplier'){
            $itemService = new SupplierItemsService();
        } else {
            $itemService = new ItemsService();
        }
        $isGetSkuList = $filter['isGetSkuList'];
        unset($filter['isGetSkuList']);

        if (isset($filter['item_id'])) {
            $filter = [
                'company_id' => $filter['company_id'],
                'item_id' => $filter['item_id']
            ];
        }
        $this->getTitle($filter['company_id']);

//        if ($isGetSkuList) {
        if (isset($filter['item_id']) && $filter['item_id']) {
            $filter['default_item_id'] = $filter['item_id'];
            unset($filter['item_id']);
        }
        $count = $itemService->getSkuItemsList($filter, 1, 1)['total_count'];
//        } else {
//            $count = $itemService->getItemsList($filter, 1, 1)['total_count'];
//        }

        if ($count <= 0) {
            return [];
        }
        $fileName = date('YmdHis') . "items";
        $dataList = $this->getLists($filter, $count, $isGetSkuList);
        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['item_bn', 'goods_bn', 'barcode'];
        $result = $exportService->exportCsv($fileName, $this->title, $dataList, $textFields);
        return $result;
    }

    private function getLists($filter, $count, $isGetSkuList)
    {
        app('log')->debug(__CLASS__.':'.__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));
        $title = $this->getTitle($filter['company_id']);
        $limit = 500;
        $totalPage = ceil($count / $limit);
        
        if($this->operator_type == 'supplier'){
            $itemService = new SupplierItemsService();
        } else {
            $itemService = new ItemsService();
        }
        
        // $colums = ['item_id', 'item_main_category' ,'item_name', 'item_bn', 'brief' , 'price', 'market_price' , 'cost_price' , 'store' , 'pics' , 'videos' , 'goods_brand' , 'templates_id' , 'item_category' , 'weight' , 'barcode' , 'attribute_name' , 'item_params'];
        for ($i = 1; $i <= $totalPage; $i++) {
            $itemsData = [];
//            if ($isGetSkuList) {
            if (isset($filter['item_id']) && $filter['item_id']) {
                $filter['default_item_id'] = $filter['item_id'];
                unset($filter['item_id']);
//                } else {
//                    $items = $itemService->getSkuItemsList($filter, $i, $limit);
//                    $itemIds = array_column($items['list'], 'item_id');
//                    $filter['default_item_id'] = $itemIds;
//                    unset($filter['is_default']);
            }
            unset($filter['is_default']);
            $result = $itemService->getSkuItemsList($filter, $i, $limit);
            $result = $this->getSkuData($result);
//            } else {
//                $result = $itemService->getItemsList($filter, $i, $limit);
//            }
            $list = $result['list'];
            // dd($filter, $list);
            
            if ($list && isset($list[0]['supplier_id']) && $this->item_source == 'supplier') {
                $supplier_ids = array_column($list, 'supplier_id');
                if ($supplier_ids) {
                    $supplierService = new SupplierService();
                    $rs = $supplierService->repository->getLists(['operator_id' => $supplier_ids], 'operator_id, supplier_name');
                    $supplierData = array_column($rs, null, 'operator_id');
                }
            }
            
            foreach ($list as $key => $value) {

                if ($value['cost_price'] && ($value['price'] > $value['cost_price'])) {
                    $gross_profit_rate = ($value['price'] - $value['cost_price']) / $value['price'];
                    $value['gross_profit_rate'] = bcmul($gross_profit_rate,'100',2).'%';
                } else {
                    $value['gross_profit_rate'] = '-';
                }
                
                foreach ($title as $field => $val) {
                    switch ($field) {
                        case 'price':
                        case 'market_price':
                        case 'cost_price':
                            if (!isset($value[$field])) break;
                            $itemsData[$key][$field] = bcdiv($value[$field], 100, 2);
                            break;
                            
                        case 'attribute_name':
                            if (isset($value['item_spec'])) {
                                $itemSpecStr = [];
                                foreach ($value['item_spec'] as $row) {
                                    $itemSpecStr[] = $row['spec_name'] . ':' . $row['spec_value_name'];
                                }
                                $itemsData[$key][$field] = implode('|', $itemSpecStr);
                            } else {
                                $itemsData[$key][$field] = '';
                            }
                            break;
                            
                        case 'pics':
                            $itemsData[$key][$field] = (isset($value['pics']) && is_array($value['pics'])) ? '"'.implode(',', $value['pics']).'"' : '';
                            break;
                            
                        case 'intro':
                            $introValue = $value['intro'] ?? '';
                            // 处理JSON数组格式（slider、imgHotzone等），提取图片URL避免CSV错列
                            $imageUrls = $this->extractImageUrlsFromJson($introValue);
                            if (!empty($imageUrls)) {
                                // 如果是JSON格式的图片数组，用分号连接（避免逗号导致CSV错列）
                                $itemsData[$key][$field] = implode(';', $imageUrls);
                            } else {
                                // 否则按富文本处理
                                $itemsData[$key][$field] = $this->formatIntroField($introValue);
                            }
                            break;
                            
                        case 'spec_pics':
                            $itemsData[$key][$field] = (isset($value['spec_pics']) && is_array($value['spec_pics'])) ? '"'.implode(',', $value['spec_pics']).'"' : '';
                            break;
                            
                        case 'videos':
                            $itemsData[$key][$field] = '';
                            break;
                            
                        case 'supplier_name':
                            $itemsData[$key][$field] = $supplierData[$value['supplier_id']]['supplier_name'] ?? '';
                            break;
                            
                        case 'item_bn':
                        case 'barcode':
                            // 直接赋值，不再判断是否为数字，不再添加引号，由 ExportFileService 统一处理
                            $itemsData[$key][$field] = $value[$field] ?? '';
                            break;
                        case 'delivery_time':
                            $itemsData[$key][$field] = isset($value['delivery_time']) && $value['delivery_time'] > 0 ? $value['delivery_time'] : '';//.'天'
                            app('log')->debug(__CLASS__.':'.__FUNCTION__.':'.__LINE__.':itemsData:'.json_encode($itemsData[$key][$field]));
                            break;
                        default:
                            $itemsData[$key][$field] = $value[$field] ?? '';
                    }
                }
            }
            // dd($itemsData);
            yield $itemsData;
        }
    }


    /**
     * API返回用的getLists方法，支持分页查询
     * @param array $filter 过滤条件
     * @param int $page 页码
     * @param int $pageSize 每页条数
     * @return array
     */
    public function getListsApiReturn($filter, $page, $pageSize)
    {
        app('log')->debug(__CLASS__.':'.__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));

        $pageSize = 200;
        $title = $this->title;
        $itemService = new ItemsService();
        
        $itemsData = [];
        if (isset($filter['item_id']) && $filter['item_id']) {
            $filter['default_item_id'] = $filter['item_id'];
            unset($filter['item_id']);
        }
        unset($filter['is_default']);
        unset($filter['operator_type']);
        unset($filter['isGetSkuList']);
        unset($filter['item_source']);//item_source
        app('log')->info(__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));
        $result = $itemService->getSkuItemsList($filter, $page, $pageSize);
        if(count($result['list']) <= 0){
            return [];
        }
        
        $result = $this->getSkuData($result);
        $list = $result['list'];
        
        if ($list && isset($list[0]['supplier_id']) && $this->item_source == 'supplier') {
            $supplier_ids = array_column($list, 'supplier_id');
            if ($supplier_ids) {
                $supplierService = new SupplierService();
                $rs = $supplierService->repository->getLists(['operator_id' => $supplier_ids], 'operator_id, supplier_name');
                $supplierData = array_column($rs, null, 'operator_id');
            }
        }
        
        foreach ($list as $key => $value) {
            if ($value['cost_price'] && ($value['price'] > $value['cost_price'])) {
                $gross_profit_rate = ($value['price'] - $value['cost_price']) / $value['price'];
                $value['gross_profit_rate'] = bcmul($gross_profit_rate,'100',2).'%';
            } else {
                $value['gross_profit_rate'] = '-';
            }
            
            foreach ($title as $field => $val) {
                switch ($field) {
                    case 'price':
                    case 'market_price':
                    case 'cost_price':
                        if (!isset($value[$field])) break;
                        $itemsData[$key][$field] = bcdiv($value[$field], 100, 2);
                        break;
                        
                    case 'attribute_name':
                        if (isset($value['item_spec'])) {
                            $itemSpecStr = [];
                            foreach ($value['item_spec'] as $row) {
                                $itemSpecStr[] = $row['spec_name'] . ':' . $row['spec_value_name'];
                            }
                            $itemsData[$key][$field] = implode('|', $itemSpecStr);
                        } else {
                            $itemsData[$key][$field] = '';
                        }
                        break;
                        
                    case 'pics':
                        $itemsData[$key][$field] = (isset($value['pics']) && is_array($value['pics'])) ? '"'.implode(',', $value['pics']).'"' : '';
                        break;
                    case 'intro':    
                        $itemsData[$key][$field] = '';//$this->formatIntroField($value['intro'] ?? '');
                        break;
                    case 'spec_pics':
                        $itemsData[$key][$field] = '';//(isset($value['spec_pics']) && is_array($value['spec_pics'])) ? '"'.implode(',', $value['spec_pics']).'"' : '';
                        break;
                    case 'videos':
                        $itemsData[$key][$field] = (isset($value['videos']) && is_array($value['videos'])) ? '"'.implode(',', $value['videos']).'"' : '';
                        break;
                        
                    case 'supplier_name':
                        $itemsData[$key][$field] = $supplierData[$value['supplier_id']]['supplier_name'] ?? '';
                        break;
                        
                    case 'item_bn':
                    case 'barcode':
                        // 直接赋值，不再判断是否为数字，不再添加引号，由 ExportFileService 统一处理
                        $itemsData[$key][$field] = $value[$field] ?? '';
                        break;
                    case 'delivery_time':
                        $itemsData[$key][$field] = isset($value['delivery_time']) && $value['delivery_time'] > 0 ? $value['delivery_time'] : '';//.'天'
                        break;                        
                    default: 
                        $itemsData[$key][$field] = $value[$field] ?? '';
                }
            }
        }
        
        return $itemsData;
    }


    /**
     * 根据商品列表，重新获取sku数据
     */
    private function getSkuData($itemsList)
    {
        $itemIds = array_column($itemsList['list'], 'default_item_id');
        $company_id = $itemsList['list'][0]['company_id'];
        $category_ids = $this->getCatIdsByItemIds($itemIds, $company_id);
        $itemRelAttributesRepository = app('registry')->getManager('default')->getRepository(ItemRelAttributes::class);
        $itemIds = array_column($itemsList['list'], 'item_id');
        // 参数
        $attrList = $itemRelAttributesRepository->lists(['item_id' => $itemIds, 'attribute_type' => ['item_params', 'brand']], 1, -1, ['attribute_sort' => 'asc']);
        $attrData = [];
        if ($attrList) {
            $attrData = $this->getRelAttrValuesList($attrList['list']);
        }
        $memberCardGradeRepository = app('registry')->getManager('default')->getRepository(MemberCardGrade::class);
        $memberCardGrade = $memberCardGradeRepository->getListByCompanyId($company_id);

        $vipGradeRepository = app('registry')->getManager('default')->getRepository(VipGrade::class);
        $vipGrade = $vipGradeRepository->lists(['company_id' => $company_id, 'is_disabled' => 0]);
        $memberPriceRepository = app('registry')->getManager('default')->getRepository(MemberPrice::class);
        $itemsProfitRepository = app('registry')->getManager('default')->getRepository(ItemsProfit::class);
        foreach ($itemsList['list'] as &$itemRow) {
            $itemParamsStr = [];
            if (isset($attrData['item_params']) && isset($attrData['item_params'][$itemRow['default_item_id']])) {
                foreach ($attrData['item_params'][$itemRow['default_item_id']] as $row) {
                    $itemParamsStr[] = $row['attribute_name'] . ':' . $row['attribute_value_name'];
                }
            }
            $itemRow['item_params'] = implode('|', $itemParamsStr);
            $itemRow['goods_brand'] = $attrData['brand'][$itemRow['default_item_id']]['goods_brand'] ?? '';
            $itemRow['templates_id'] = $this->getTemplatesName($itemRow['company_id'], $itemRow['templates_id']);
            $itemRow['item_main_category'] = $this->getItemCategory($itemRow['company_id'], $itemRow['item_main_cat_id'], 1);
            $item_category = $category_ids[$itemRow['default_item_id']] ?? 0;
            $itemRow['item_category'] = $this->getItemCategory($itemRow['company_id'], $item_category, 0);
            $approve_status = ['onsale' => '前台可销售', 'offline_sale' => '前端不展示', 'instock' => '不可销售', 'only_show' => '前台仅展示'];
            $itemRow['approve_status'] = $approve_status[$itemRow['approve_status']];
            //销售价格
            $price = bcdiv($itemRow['price'],100,10);
            //成本格
            $cost_price = bcdiv($itemRow['cost_price'],100,10);
            $audit_status = ['submitting' => '待提交', 'approved' => '已通过', 'processing' => '待审核', 'rejected' => '已拒绝'];
            $itemRow['audit_status'] = $audit_status[$itemRow['audit_status']];
            $is_market = ['0' => '不可售', '1' => '可售'];
            $itemRow['is_market'] = $is_market[$itemRow['is_market']];
            $promotionPrice = $memberPriceRepository->getInfo(['company_id' => $itemRow['company_id'], 'item_id' => $itemRow['item_id']]);
            $memberCardGradePrice = [];
            $vipGradePrice = [];
            if (!empty($promotionPrice['mprice'])) {
                $arrPromotionPrice = json_decode($promotionPrice['mprice'], true);
                $memberCardGradePrice = $arrPromotionPrice['grade'];
                $vipGradePrice = $arrPromotionPrice['vipGrade'];
            }
            if (!empty($memberCardGrade)) {
                foreach ($memberCardGrade as $key => $value) {
                    $grade_key = 'grade_price' . $value['grade_id'];
                    if (!empty($memberCardGradePrice[$value['grade_id']])) {
                        $itemRow[$grade_key] = bcdiv($memberCardGradePrice[$value['grade_id']], 100, 2);
                    } else {
                        $itemRow[$grade_key] = '';
                    }
                }
            }
            if (!empty($vipGrade)) {
                foreach ($vipGrade as $key => $vipValue) {
                    $vip_grade_key = 'vip_grade_price' . $vipValue['vip_grade_id'];
                    if (!empty($vipGradePrice[$vipValue['vip_grade_id']])) {
                        $itemRow[$vip_grade_key] = bcdiv($vipGradePrice[$vipValue['vip_grade_id']], 100, 2);
                    } else {
                        $itemRow[$vip_grade_key] = '';
                    }
                }
            }
            $itemProfit = $itemsProfitRepository->getInfo(['company_id' => $itemRow['company_id'], 'item_id' => $itemRow['item_id']]);
            $itemRow['is_profit'] = !empty($itemRow['is_profit']) ? 1 : 0;
            $itemRow['profit'] = '';
            $itemRow['popularize_profit'] = '';
            if (!empty($itemProfit)) {
                $itemRow['profit_type'] = $itemProfit['profit_type'];
                $profitConf = $itemProfit['profit_conf'];
                if ($itemProfit['profit_type'] == 1) {
                    $itemRow['profit'] = $profitConf['profit'];
                    $itemRow['popularize_profit'] = $profitConf['popularize_profit'];
                }
                if ($itemProfit['profit_type'] == 2) {
                    $itemRow['profit'] = bcdiv($profitConf['profit'], 100, 2);
                    $itemRow['popularize_profit'] = bcdiv($profitConf['popularize_profit'], 100, 2);
                }
            }
        }
        unset($itemRow);
        return $itemsList;
    }

    /**
     * 获取商品关联的属性值
     */
    private function getRelAttrValuesList($data)
    {
        if (!$data) {
            return [];
        }

        $attributeValuesIds = [];
        $attributeValuesImgs = [];
        $attributeValuesCustomName = [];
        $itemParamsCustomName = [];
        $attributeIds = [];
        foreach ($data as $row) {
            if ($row['attribute_value_id']) {
                $attributeValuesIds[] = $row['attribute_value_id'];
                if (!isset($attributeValuesImgs[$row['attribute_value_id']]) || !$attributeValuesImgs[$row['attribute_value_id']]) {
                    $attributeValuesImgs[$row['attribute_value_id']] = $row['image_url'];
                }
                $attributeValuesCustomName[$row['attribute_value_id']] = $row['custom_attribute_value'];
            }

            if ($row['attribute_type'] == 'item_params') {
                $itemParamsCustomName[$row['attribute_id']] = $row['custom_attribute_value'];
            }
            $attributeIds[] = $row['attribute_id'];
        }

        $attributeIds = array_unique($attributeIds);
        $attributeValuesIds = array_unique($attributeValuesIds);

        $return['attr_values_custom'] = $attributeValuesCustomName;
        $itemsAttributesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributes::class);

        $attrList = $itemsAttributesRepository->lists(['attribute_id' => $attributeIds], 1, -1);
        $attrListNew = array_column($attrList['list'], null, 'attribute_id');

        $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
        $attrValuesList = $itemsAttributesValuesRepository->lists(['attribute_value_id' => $attributeValuesIds, 'attribute_value_id|neq' => null], 1, -1);
        $attrValuesListNew = array_column($attrValuesList['list'], null, 'attribute_value_id');

        $itemSpecDesc = [];
        $itemParams = [];
        foreach ($data as $row) {
            if ($row['attribute_type'] == 'brand') {
                $return['brand'][$row['item_id']]['brand_id'] = $row['attribute_id'];
                $return['brand'][$row['item_id']]['goods_brand'] = $attrListNew[$row['attribute_id']]['attribute_name'];
                $return['brand'][$row['item_id']]['brand_logo'] = $row['image_url'];
            } else {
                $return['attribute_ids'][$row['item_id']][] = $row['attribute_id'];
            }

            if ($row['attribute_type'] == 'item_params' && ($row['attribute_value_id'] || $itemParamsCustomName[$row['attribute_id']])) {
                $oldAttributeValueName = isset($attrValuesListNew[$row['attribute_value_id']]) ? $attrValuesListNew[$row['attribute_value_id']]['attribute_value'] : '';
//                $attributeValueName = $itemParamsCustomName[$row['attribute_id']] ?: $oldAttributeValueName;
                // 上面取值错误，如果是自定义参数直接取值custom_attribute_value
                $attributeValueName = $row['custom_attribute_value'] ?: $oldAttributeValueName;
                $return['item_params'][$row['item_id']][$row['attribute_id']] = [
                    'attribute_id' => $row['attribute_id'],
                    'attribute_name' => $attrListNew[$row['attribute_id']]['attribute_name'],
                    'attribute_value_id' => $row['attribute_value_id'],
                    'attribute_value_name' => $attributeValueName,
                ];
            }
        }

        return $return;
    }

    /**
     * 通过运费模版名称，获取运费模版名称
     */
    private function getTemplatesName($companyId, $templates_id)
    {
        $shippingTemplatesService = new ShippingTemplatesService();
        $data = $shippingTemplatesService->getInfo($templates_id, $companyId);
        return $data['name'] ?? '';
    }

    /**
     * 根据item_id获取分类Id
     * @param $itemIds :商品Id数组
     * @param $companyId :企业Id
     * @return $catIds array 分类数组
     */
    private function getCatIdsByItemIds($itemIds, $companyId)
    {
        $itemsService = new ItemsRelCatsService();
        $filter['item_id'] = $itemIds;
        $filter['company_id'] = $companyId;
        $data = $itemsService->lists($filter);
        $catIds = [];
        if ($data['list']) {
            foreach ($data['list'] as $value) {
                $catIds[$value['item_id']][] = $value['category_id'];
            }
        }
        return $catIds;
    }

    /**
     * 获取商品分类名称
     * 主类目：一级类目->二级类目->三级类目
     * 分类：一级分类->二级分类|一级分类->二级分类>三级分类 多个二级三级分类使用|隔开
     */
    private function getItemCategory($companyId, $categoryId, $isMain = false)
    {
        if (!$categoryId) {
            return '';
        }
        $itemsCategoryService = new ItemsCategoryService();
        if ($isMain) {
            $lists = $itemsCategoryService->getCategoryPathById($categoryId, $companyId, $isMain);
            $category_name = [];
            $this->getCategoryName($lists[0], $category_name);
            $item_category = implode('->', $category_name);
            return $item_category;
        } else {
            $category = [];
            foreach ($categoryId as $key => $value) {
                $lists = $itemsCategoryService->getCategoryPathById($value, $companyId, $isMain);
                if ($lists) {
                    $category_name = [];
                    $this->getCategoryName($lists[0], $category_name);
                    $_category_name = implode('->', $category_name);
                    $category[] = $_category_name;
                }
            }
            $item_category = implode('|', $category);
            return $item_category;
        }
    }

    /**
     * 获取分类名称
     */
    private function getCategoryName($list, &$category_name)
    {
        $category_name[] = $list['category_name'];
        if (isset($list['children']) && $list['children']) {
            $this->getCategoryName($list['children'][0], $category_name);
        }
    }

    /**
     * 获取title
     * @param $companyId
     * @return false|string[]
     */
    public function getTitle($companyId)
    {
        if (is_array($companyId)) {
            $companyId = $companyId['company_id'];
        }
        if ($this->item_source != 'supplier') {
            unset($this->title['supplier_name']);//只有平台导出供应商的时候需要
        }
        if ($this->operator_type == 'supplier') {
            unset($this->title['approve_status']);//供应商不需要商品状态
            return $this->title;//供应商商品导出不需要会员价
        } else {
            unset($this->title['audit_status']);//平台不需要审核状态
            unset($this->title['is_market']);//只有供应商导出的时候需要
            return $this->addMemberPriceHeader($companyId);
        }        
    }

    /**
     * 增加支持会员价字段导入
     * @param int $companyId
     * @return false|string[]
     */
    public function addMemberPriceHeader($companyId = 0)
    {
        if (!$companyId) {
            return false;
        }

        //获取VIP会员等级
        $vipGradeService = new VipGradeService();
        $vipGrade = $vipGradeService->lists(['company_id' => $companyId, 'is_disabled' => false]);
        if ($vipGrade) {
            $vipGrade = array_column($vipGrade, null, 'vip_grade_id');
        }


        //获取普通会员等级
        $kaquanService = new MemberCardService();
        $userGrade = $kaquanService->getGradeListByCompanyId($companyId, false);
        if ($userGrade) {
            $userGrade = array_column($userGrade, null, 'grade_id');
        }
        $this->_setHeader($userGrade, $vipGrade);

        return $this->title;
    }

    /**
     * 设置会员价导入头信息
     *
     * @param array $userGrade
     * @param array $vipGrade
     */
    private function _setHeader($userGrade = [], $vipGrade = [])
    {
        $newHeader = [];
        foreach ($this->title as $k => $v) {
            if ($k != self::MEMBER_PRICE_KEY) {
                $newHeader[$k] = $v;
                continue;
            }

            foreach ($userGrade as $grade) {
                $gradeKey = 'grade_price' . $grade['grade_id'];
                $newHeader[$gradeKey] = $grade['grade_name'];
            }

            foreach ($vipGrade as $grade) {
                $vipGradeKey = 'vip_grade_price' . $grade['vip_grade_id'];
                $newHeader[$vipGradeKey] = $grade['grade_name'];
            }
        }

        $this->title = $newHeader;
    }

    /**
     * 从intro中提取JSON格式的图片URL（支持slider和imgHotzone格式）
     * 
     * @param mixed $intro intro字段值（可能是数组、JSON字符串或其他格式）
     * @return array 图片URL数组，如果不是JSON格式则返回空数组
     */
    private function extractImageUrlsFromJson($intro)
    {
        if (empty($intro)) {
            return [];
        }

        $dataArray = null;

        // 处理数组格式
        if (is_array($intro)) {
            $dataArray = $intro;
        }
        // 处理JSON字符串格式
        elseif (is_string($intro) && trim($intro) !== '') {
            // 尝试解析JSON
            $decoded = json_decode($intro, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $dataArray = $decoded;
            } else {
                // 不是有效的JSON，返回空数组
                return [];
            }
        } else {
            return [];
        }

        // 检查是否是JSON数组格式
        if (!is_array($dataArray) || empty($dataArray)) {
            return [];
        }

        $imageUrls = [];
        $isJsonFormat = false;

        foreach ($dataArray as $item) {
            if (!is_array($item)) {
                continue;
            }

            // 处理slider格式：从data数组中提取imgUrl
            if (isset($item['name']) && $item['name'] === 'slider' && isset($item['data']) && is_array($item['data'])) {
                $isJsonFormat = true;
                foreach ($item['data'] as $dataItem) {
                    if (is_array($dataItem) && isset($dataItem['imgUrl'])) {
                        $imageUrls[] = $dataItem['imgUrl'];
                    }
                }
            }
            // 处理imgHotzone格式：从config中提取imgUrl
            elseif (isset($item['name']) && $item['name'] === 'imgHotzone' && isset($item['config']['imgUrl'])) {
                $isJsonFormat = true;
                $imageUrls[] = $item['config']['imgUrl'];
            }
            // 兼容其他可能的格式：有config.imgUrl但没有name字段
            elseif (isset($item['config']['imgUrl'])) {
                $isJsonFormat = true;
                $imageUrls[] = $item['config']['imgUrl'];
            }
            // 兼容：有data数组且包含imgUrl
            elseif (isset($item['data']) && is_array($item['data'])) {
                foreach ($item['data'] as $dataItem) {
                    if (is_array($dataItem) && isset($dataItem['imgUrl'])) {
                        $isJsonFormat = true;
                        $imageUrls[] = $dataItem['imgUrl'];
                    }
                }
            }
        }

        // 只有确认是JSON格式才返回URL数组
        return $isJsonFormat ? $imageUrls : [];
    }

    /**
     * 格式化intro字段，保持HTML内容的完整性
     * 支持包含图片标签的复杂HTML内容导出和导入
     * 
     * @param mixed $intro 商品详情内容
     * @return string 格式化后的内容
     */
    private function formatIntroField($intro)
    {
        if (empty($intro)) {
            return '';
        }

        // 如果intro是数组，转换为字符串
        if (is_array($intro)) {
            $intro = implode('', $intro);
        }

        // 如果intro是字符串但为空，直接返回
        if (!is_string($intro) || trim($intro) === '') {
            return '';
        }

        // 处理HTML内容，确保在Excel中正确显示
        $formattedIntro = $this->processHtmlContent($intro);
        
        // 使用特殊分隔符包装，便于导入时识别和解析
        return '<!--HTML_CONTENT_START-->' . $formattedIntro . '<!--HTML_CONTENT_END-->';
    }

    /**
     * 处理HTML内容，确保在Excel中正确显示和导入
     * 
     * @param string $htmlContent HTML内容
     * @return string 处理后的内容
     */
    private function processHtmlContent($htmlContent)
    {
        // 移除多余的空白字符，但保持HTML结构
        $htmlContent = preg_replace('/\s+/', ' ', trim($htmlContent));
        
        // 确保图片标签的完整性
        $htmlContent = $this->normalizeImageTags($htmlContent);
        
        // 处理特殊字符，确保在CSV中正确显示
        $htmlContent = $this->escapeSpecialCharacters($htmlContent);
        
        return $htmlContent;
    }

    /**
     * 标准化图片标签，确保图片URL的完整性
     * 
     * @param string $htmlContent HTML内容
     * @return string 处理后的内容
     */
    private function normalizeImageTags($htmlContent)
    {
        // 查找所有img标签
        preg_match_all('/<img[^>]*>/i', $htmlContent, $matches);
        
        foreach ($matches[0] as $imgTag) {
            // 提取src属性
            if (preg_match('/src\s*=\s*["\']([^"\']+)["\']/i', $imgTag, $srcMatch)) {
                $imageUrl = $srcMatch[1];
                
                // 确保图片URL是完整的
                if (!preg_match('/^https?:\/\//', $imageUrl)) {
                    // 如果是相对路径，可以在这里添加域名前缀
                    // $imageUrl = 'https://your-domain.com' . $imageUrl;
                }
                
                // 重新构建img标签，确保格式一致
                $newImgTag = '<img src="' . $imageUrl . '" alt="商品图片" />';
                $htmlContent = str_replace($imgTag, $newImgTag, $htmlContent);
            }
        }
        
        return $htmlContent;
    }

    /**
     * 转义特殊字符，确保在CSV中正确显示
     * 
     * @param string $content 内容
     * @return string 转义后的内容
     */
    private function escapeSpecialCharacters($content)
    {
        // 转义CSV中的特殊字符
        $content = str_replace('"', '""', $content);
        
        // 处理换行符，确保在Excel单元格中正确显示
        $content = str_replace(["\r\n", "\r", "\n"], ' ', $content);
        
        // 移除制表符
        $content = str_replace("\t", ' ', $content);
        
        return $content;
    }

    /**
     * 解析导入的intro字段内容，恢复HTML格式
     * 用于导入Excel数据时处理intro字段
     * 
     * @param string $importedContent 导入的内容
     * @return string 解析后的HTML内容
     */
    public function parseImportedIntroField($importedContent)
    {
        if (empty($importedContent)) {
            return '';
        }

        // 检查是否包含HTML内容标记
        if (strpos($importedContent, '<!--HTML_CONTENT_START-->') !== false && 
            strpos($importedContent, '<!--HTML_CONTENT_END-->') !== false) {
            
            // 提取HTML内容
            $startPos = strpos($importedContent, '<!--HTML_CONTENT_START-->') + strlen('<!--HTML_CONTENT_START-->');
            $endPos = strpos($importedContent, '<!--HTML_CONTENT_END-->');
            $htmlContent = substr($importedContent, $startPos, $endPos - $startPos);
            
            // 清理和恢复HTML内容
            return $this->restoreHtmlContent($htmlContent);
        }

        // 如果没有标记，直接返回原内容（向后兼容）
        return $importedContent;
    }

    /**
     * 恢复HTML内容，处理导入时可能被修改的字符
     * 
     * @param string $htmlContent HTML内容
     * @return string 恢复后的内容
     */
    private function restoreHtmlContent($htmlContent)
    {
        // 恢复被转义的双引号
        $htmlContent = str_replace('""', '"', $htmlContent);
        
        // 恢复图片标签的完整性
        $htmlContent = $this->restoreImageTags($htmlContent);
        
        // 清理多余的空白字符
        $htmlContent = preg_replace('/\s+/', ' ', trim($htmlContent));
        
        return $htmlContent;
    }

    /**
     * 恢复图片标签的完整性
     * 
     * @param string $htmlContent HTML内容
     * @return string 恢复后的内容
     */
    private function restoreImageTags($htmlContent)
    {
        // 查找所有img标签
        preg_match_all('/<img[^>]*>/i', $htmlContent, $matches);
        
        foreach ($matches[0] as $imgTag) {
            // 检查img标签是否完整
            if (!preg_match('/src\s*=\s*["\']([^"\']+)["\']/i', $imgTag)) {
                // 如果img标签不完整，尝试修复
                $fixedImgTag = $this->fixBrokenImageTag($imgTag);
                if ($fixedImgTag) {
                    $htmlContent = str_replace($imgTag, $fixedImgTag, $htmlContent);
                }
            }
        }
        
        return $htmlContent;
    }

    /**
     * 修复损坏的图片标签
     * 
     * @param string $brokenTag 损坏的标签
     * @return string|null 修复后的标签，如果无法修复返回null
     */
    private function fixBrokenImageTag($brokenTag)
    {
        // 尝试从损坏的标签中提取有用信息
        if (preg_match('/src\s*=\s*([^>\s]+)/i', $brokenTag, $srcMatch)) {
            $src = trim($srcMatch[1], '"\'');
            return '<img src="' . $src . '" alt="商品图片" />';
        }
        
        return null;
    }
}
