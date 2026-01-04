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

namespace SupplierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use GoodsBundle\Entities\ItemsAttributes;
use GoodsBundle\Entities\ItemsAttributeValues;
use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsRelCatsService;
use OrdersBundle\Services\ShippingTemplatesService;
use SupplierBundle\Services\SupplierItemsAttrService;
use SupplierBundle\Services\SupplierItemsService;
use SupplierBundle\Services\SupplierService;

class SupplierGoodsExportService implements ExportFileInterface
{
    private $title = [
        'item_main_category' => '管理分类',
        'item_name' => '商品名称',
        'goods_bn' => 'SPU编码',
        'item_bn' => 'SKU编码',
        // 'supplier_goods_bn' => '供应商商品货号',
        // 'supplier_name' => '供应商名称',
        'brief' => '简介',
        'price' => '销售价',
        'market_price' => '市场价',
        'cost_price' => '成本价',
        // 'member_price' => '会员价', ##会被替换
        'start_num' => '起订量',
        // 'member_price' => '会员价', ##会被替换
        // 'gross_profit_rate' => '毛利率(%)', ##会被替换
        'store' => '库存',
        'pics' => '图片',
        'videos' => '视频',
        'goods_brand' => '品牌',
        'templates_id' => '运费模板',
        'item_category' => '销售分类',
        'weight' => '重量',
        'barcode' => '条形码',
        'item_unit' => '单位',
        'attribute_name' => '规格值',
        'item_params' => '参数值',
        // 'is_profit' => '是否支持分润',
        // 'profit_type' => '分润类型',
        // 'profit' => '拉新分润',
        // 'popularize_profit' => '推广分润',
        // 'approve_status' => '商品状态',
        'is_market' => '供应状态',
        // 'audit_status' => '审核状态',
    ];

    public $operator_type = '';//区分供应商，商户，店铺，平台
    public $item_source = '';//区分供应商商品 supplier，平台自营商品 platform

    // TODO: Implement exportData() method.
    public function exportData($filter)
    {
        // ShopEx EcShopX Core Module
        unset($filter['distributor_id']);
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
        $itemService = new SupplierItemsService();
        $count = $itemService->getSkuItemsList($filter, 1, 1)['total_count'];
        //        } else {
        //            $count = $itemService->getItemsList($filter, 1, 1)['total_count'];
        //        }

        if ($count <= 0) {
            return [];
        }
        $fileName = date('YmdHis') . "_supplier_items";
        app('log')->debug(__CLASS__.':'.__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));
        $dataList = $this->getLists($filter, $count, $isGetSkuList);
        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $dataList);
        return $result;
    }

    private function getLists($filter, $count, $isGetSkuList)
    {
        app('log')->debug(__CLASS__.':'.__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));

        $title = $this->getTitle($filter['company_id']);
        $limit = 500;
        $totalPage = ceil($count / $limit);

        $itemService = new SupplierItemsService();

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

                        case 'videos':
                            $itemsData[$key][$field] = '';
                            break;

                        case 'supplier_name':
                            $itemsData[$key][$field] = $supplierData[$value['supplier_id']]['supplier_name'] ?? '';
                            break;

                        case 'item_bn':
                        case 'barcode':
                            if (is_numeric($value[$field])) {
                                $itemsData[$key][$field] = "\"'".$value[$field]."\"";
                            } else {
                                $itemsData[$key][$field] = $value[$field];
                            }
                            break;

                        default:
                            $itemsData[$key][$field] = $value[$field] ?? '';
                    }
                }
            }
            yield $itemsData;
        }
    }

    /**
     * 根据商品列表，重新获取sku数据
     */
    private function getSkuData($itemsList)
    {
        $company_id = $itemsList['list'][0]['company_id'];
        $SupplierItemsAttrService = new SupplierItemsAttrService();

        //获取商品销售分类id
        // $category_ids = $this->getCatIdsByItemIds($itemIds, $company_id);
        $itemIds = array_column($itemsList['list'], 'default_item_id');
        $category_ids = $SupplierItemsAttrService->getAttrDataBatch($itemIds, $company_id, 'category');

        //获取商品关联属性
        $itemIds = array_column($itemsList['list'], 'item_id');
        $attrList = $SupplierItemsAttrService->getItemRelAttr($itemIds, ['item_params', 'brand']);
        // $itemRelAttributesRepository = app('registry')->getManager('default')->getRepository(ItemRelAttributes::class);
        // $attrList = $itemRelAttributesRepository->lists(['item_id' => $itemIds, 'attribute_type' => ['item_params', 'brand']], 1, -1, ['attribute_sort' => 'asc']);
        $attrData = [];
        if ($attrList) {
            $attrData = $this->getRelAttrValuesList($attrList);
        }
        // $memberCardGradeRepository = app('registry')->getManager('default')->getRepository(MemberCardGrade::class);
        // $memberCardGrade = $memberCardGradeRepository->getListByCompanyId($company_id);

        // $vipGradeRepository = app('registry')->getManager('default')->getRepository(VipGrade::class);
        // $vipGrade = $vipGradeRepository->lists(['company_id' => $company_id, 'is_disabled' => 0]);
        // $memberPriceRepository = app('registry')->getManager('default')->getRepository(MemberPrice::class);
        // $itemsProfitRepository = app('registry')->getManager('default')->getRepository(ItemsProfit::class);
        foreach ($itemsList['list'] as &$itemRow) {
            $itemRow['item_main_cat_id'] = $itemRow['item_category'];
            //商品参数
            $itemParamsStr = [];
            if (isset($attrData['item_params']) && isset($attrData['item_params'][$itemRow['default_item_id']])) {
                foreach ($attrData['item_params'][$itemRow['default_item_id']] as $row) {
                    $itemParamsStr[] = $row['attribute_name'] . ':' . $row['attribute_value_name'];
                }
            }
            $itemRow['item_params'] = implode('|', $itemParamsStr);
            //品牌
            $itemRow['goods_brand'] = $attrData['brand'][$itemRow['default_item_id']]['goods_brand'] ?? '';
            //运费模板
            $itemRow['templates_id'] = $this->getTemplatesName($itemRow['company_id'], $itemRow['templates_id']);
            //商品主类目
            $itemRow['item_main_category'] = $this->getItemCategory($itemRow['company_id'], $itemRow['item_main_cat_id'], 1);
            //商品销售分类
            $item_category = $category_ids[$itemRow['default_item_id']] ?? 0;
            $itemRow['item_category'] = $this->getItemCategory($itemRow['company_id'], $item_category, 0);

            // $approve_status = ['onsale' => '前台可销售', 'offline_sale' => '前端不展示', 'instock' => '不可销售', 'only_show' => '前台仅展示'];
            // $itemRow['approve_status'] = $approve_status[$itemRow['approve_status']];
            //销售价格
            // $price = bcdiv($itemRow['price'],100,10);
            //成本格
            // $cost_price = bcdiv($itemRow['cost_price'],100,10);

            // $audit_status = ['submitting' => '待提交', 'approved' => '已通过', 'processing' => '待审核', 'rejected' => '已拒绝'];
            // $itemRow['audit_status'] = $audit_status[$itemRow['audit_status']];

            $is_market = ['0' => '不可售', '1' => '可售'];
            $itemRow['is_market'] = $is_market[$itemRow['is_market']];

            // $promotionPrice = $memberPriceRepository->getInfo(['company_id' => $itemRow['company_id'], 'item_id' => $itemRow['item_id']]);
            // $memberCardGradePrice = [];

            // $vipGradePrice = [];
            // if (!empty($promotionPrice['mprice'])) {
            //     $arrPromotionPrice = json_decode($promotionPrice['mprice'], true);
            //     $memberCardGradePrice = $arrPromotionPrice['grade'];
            //     $vipGradePrice = $arrPromotionPrice['vipGrade'];
            // }

            //会员卡
            // if (!empty($memberCardGrade)) {
            //     foreach ($memberCardGrade as $key => $value) {
            //         $grade_key = 'grade_price' . $value['grade_id'];
            //         if (!empty($memberCardGradePrice[$value['grade_id']])) {
            //             $itemRow[$grade_key] = bcdiv($memberCardGradePrice[$value['grade_id']], 100, 2);
            //         } else {
            //             $itemRow[$grade_key] = '';
            //         }
            //     }
            // }

            //会员价
            // if (!empty($vipGrade)) {
            //     foreach ($vipGrade as $key => $vipValue) {
            //         $vip_grade_key = 'vip_grade_price' . $vipValue['vip_grade_id'];
            //         if (!empty($vipGradePrice[$vipValue['vip_grade_id']])) {
            //             $itemRow[$vip_grade_key] = bcdiv($vipGradePrice[$vipValue['vip_grade_id']], 100, 2);
            //         } else {
            //             $itemRow[$vip_grade_key] = '';
            //         }
            //     }
            // }

            // $itemProfit = $itemsProfitRepository->getInfo(['company_id' => $itemRow['company_id'], 'item_id' => $itemRow['item_id']]);
            $itemRow['is_profit'] = !empty($itemRow['is_profit']) ? 1 : 0;
            $itemRow['profit'] = '';
            $itemRow['popularize_profit'] = '';
            // if (!empty($itemProfit)) {
            //     $itemRow['profit_type'] = $itemProfit['profit_type'];
            //     $profitConf = $itemProfit['profit_conf'];
            //     if ($itemProfit['profit_type'] == 1) {
            //         $itemRow['profit'] = $profitConf['profit'];
            //         $itemRow['popularize_profit'] = $profitConf['popularize_profit'];
            //     }
            //     if ($itemProfit['profit_type'] == 2) {
            //         $itemRow['profit'] = bcdiv($profitConf['profit'], 100, 2);
            //         $itemRow['popularize_profit'] = bcdiv($profitConf['popularize_profit'], 100, 2);
            //     }
            // }
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
        return $this->title;
    }
}
