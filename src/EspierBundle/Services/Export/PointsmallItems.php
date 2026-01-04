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
use PointsmallBundle\Entities\PointsmallItemRelAttributes as ItemRelAttributes;
use PointsmallBundle\Entities\PointsmallItemsAttributes as ItemsAttributes;
use PointsmallBundle\Entities\PointsmallItemsAttributeValues as ItemsAttributeValues;
use PointsmallBundle\Services\ItemsService;
use OrdersBundle\Services\ShippingTemplatesService;
use PointsmallBundle\Services\ItemsRelCatsService;
use PointsmallBundle\Services\ItemsCategoryService;

class PointsmallItems implements ExportFileInterface
{
    private $title = [
        'item_main_category' => '管理分类',
        'item_name' => '商品名称',
        'item_bn' => '商品编码',
        'brief' => '简介',
        'price' => '商品价格',
        'market_price' => '市场价',
        'cost_price' => '成本价',
        'point' => '积分价格',
        'store' => '库存',
        'pics' => '图片',
        'videos' => '视频',
        'goods_brand' => '品牌',
        'templates_id' => '运费模板',
        'item_category' => '分类',
        'weight' => '重量',
        'barcode' => '条形码',
        'item_unit' => '单位',
        'attribute_name' => '规格值',
        'item_params' => '参数值',
    ];

    public function exportData($filter)
    {
        // TODO: Implement exportData() method.
        $itemService = new ItemsService();
        $isGetSkuList = $filter['isGetSkuList'];
        unset($filter['isGetSkuList']);

        if (isset($filter['item_id'])) {
            $filter = [
                'company_id' => $filter['company_id'],
                'item_id' => $filter['item_id']
            ];
        }

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
        $fileName = date('YmdHis')."items";
        $dataList = $this->getLists($filter, $count, $isGetSkuList);

        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['item_bn', 'barcode'];
        $result = $exportService->exportCsv($fileName, $this->title, $dataList, $textFields);
        return $result;
    }

    private function getLists($filter, $count, $isGetSkuList)
    {
        $title = $this->title;
        $limit = 500;
        $totalPage = ceil($count / $limit);
        $itemService = new ItemsService();
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
            foreach ($list as $key => $value) {
                foreach ($title as $k => $val) {
                    if (in_array($k, ['price','market_price','cost_price']) && isset($value[$k])) {
                        $itemsData[$key][$k] = bcdiv($value[$k], 100, 2);
                    } elseif ($k == 'attribute_name') {
                        if (isset($value['item_spec_desc'])) {
                            $itemsData[$key][$k] = $value['item_spec_desc'];
                        } else {
                            $itemsData[$key][$k] = '';
                        }
                    } elseif ($k == 'pics') {
                        $itemsData[$key][$k] = (isset($value['pics']) && is_array($value['pics'])) ? implode(',', $value['pics']) : '';
                    } elseif ($k == 'videos') {
                        $itemsData[$key][$k] = '';
                    } elseif (in_array($k, ['item_bn', 'barcode'])) {
                        // 直接赋值，不再判断是否为数字，不再添加引号，由 ExportFileService 统一处理
                        $itemsData[$key][$k] = $value[$k] ?? '';
                    } elseif (isset($value[$k])) {
                        $itemsData[$key][$k] = $value[$k];
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
        $itemIds = array_column($itemsList['list'], 'default_item_id');
        $company_id = $itemsList['list'][0]['company_id'];
        $category_ids = $this->getCatIdsByItemIds($itemIds, $company_id);
        $itemRelAttributesRepository = app('registry')->getManager('default')->getRepository(ItemRelAttributes::class);
        $itemIds = array_column($itemsList['list'], 'item_id');
        // 参数
        $attrList = $itemRelAttributesRepository->lists(['item_id' => $itemIds, 'attribute_type' => ['item_params','brand']], 1, -1, ['attribute_sort' => 'asc']);
        $attrData = [];
        if ($attrList) {
            $attrData = $this->getRelAttrValuesList($attrList['list']);
        }
        foreach ($itemsList['list'] as &$itemRow) {
            $itemParamsStr = [];
            if (isset($attrData['item_params']) && isset($attrData['item_params'][$itemRow['default_item_id']])) {
                foreach ($attrData['item_params'][$itemRow['default_item_id']] as $row) {
                    $itemParamsStr[] = $row['attribute_name'].':'.$row['attribute_value_name'];
                }
            }
            $itemRow['item_params'] = implode('|', $itemParamsStr);
            $itemRow['goods_brand'] = $attrData['brand'][$itemRow['default_item_id']]['goods_brand'] ?? '';
            $itemRow['templates_id'] = $this->getTemplatesName($itemRow['company_id'], $itemRow['templates_id']);
            $itemRow['item_main_category'] = $this->getItemCategory($itemRow['company_id'], $itemRow['item_main_cat_id'], 1);
            $item_category = $category_ids[$itemRow['default_item_id']] ?? 0;
            $itemRow['item_category'] = $this->getItemCategory($itemRow['company_id'], $item_category, 0);
        }
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
                $attributeValueName = $itemParamsCustomName[$row['attribute_id']] ?: $oldAttributeValueName;
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
    * @param $itemIds:商品Id数组
    * @param $companyId:企业Id
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
}
