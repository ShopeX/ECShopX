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

use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsTagsService;
use GoodsBundle\Services\ItemsCategoryService;
use OrdersBundle\Services\ShippingTemplatesService;
use GoodsBundle\Services\ItemsAttributesService;
use GoodsBundle\Services\ItemRelAttributesService;
use DistributionBundle\Services\DistributorService;

class NormalItemsExportService implements ExportFileInterface
{
    public function exportData($filter)
    {
        $distributorId = $filter['distributor_id'] ?? 0;
        unset($filter['distributor_id']);

        $itemsService = new ItemsService();
        $count = $itemsService->getItemCount($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis') . "normal_items";
        $itemList = $this->getLists($filter, $count, $distributorId);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $itemList);
        return $result;
    }

    private $title = [
        'goods_id' => '商品id',
        'item_name' => '商品名称',
        'brief' => '商品副标题',
        'barcode' => '商品条码',
        'pics' => '商品图片',
        'item_main_category' => '管理分类',
        'item_category' => '前端分类',
        'goods_brand' => '品牌',
        'origin_place' => '产地',
        'tag_name' => '商品标签',
        'item_unit' => '销售单位',
        'item_spec' => '销售规格',
        'item_params' => '商品参数',
        'approve_status' => '商品状态',
        'price' => '商品售价',
        'market_price' => '商品划线价',
        'cost_price' => '商品进价',
        'intro' => '简介',
        'templates_id' => '运费模板',
        'weight' => '重量',
        'volume' => '体积',
        'storage_way' => '贮存方式',
        'specification' => '商品规格',
    ];

    private function getLists($filter, $count, $distributorId = 0)
    {
        $title = $this->title;
        if ($count > 0) {
            $itemsService = new ItemsService();
            $itemsTagsService = new ItemsTagsService();
            if ($distributorId) {
                $distributorService = new DistributorService();
                $distributorInfo = $distributorService->getInfo(['distributor_id' => $distributorId, 'company_id' => $filter['company_id']]);
            }
            $limit = 500;
            $fileNum = ceil($count / $limit);
            for ($page = 1; $page <= $fileNum; $page++) {
                $itemData = [];
                $result = $itemsService->getItemsList($filter, $page, $limit);

                //获取商品标签
                $itemIds = array_column($result['list'], 'item_id');
                $tagFilter = [
                    'item_id' => $itemIds,
                    'company_id' => $filter['company_id'],
                ];

                $tagList = $itemsTagsService->getItemsRelTagList($tagFilter);
                foreach ($tagList as $tag) {
                    $newTags[$tag['item_id']][] = $tag;
                }

                foreach ($result['list'] as $i => $value) {
                    foreach ($title as $key => $val) {
                        if ($key == 'shop_code') {
                            $itemData[$i][$key] = $distributorInfo['shop_code'] ?? '无';
                        } elseif ($key == 'name') {
                            $itemData[$i][$key] = $distributorInfo['name'] ?? '无';
                        } elseif ($key == 'tag_name') {
                            $itemTag = $newTags[$value['item_id']] ?? [];
                            $itemData[$i][$key] = implode(',', array_column($itemTag, 'tag_name'));
                        } elseif ($key == 'item_main_category') {
                            $itemData[$i][$key] = $this->getItemCategory($filter['company_id'], $value, true);
                        } elseif ($key == 'item_category') {
                            $itemData[$i][$key] = $this->getItemCategory($filter['company_id'], $value, false);
                        } elseif ($key == 'goods_brand') {
                            $itemData[$i][$key] = $this->getBrandName($filter['company_id'], $value);
                        } elseif ($key == 'templates_id') {
                            $itemData[$i][$key] = $this->getTemplatesName($filter['company_id'], $value);
                        } elseif ($key == 'pics') {
                            $itemData[$i][$key] = is_array($value[$key]) ? implode(',', $value[$key]) : $value[$key];
                        } elseif ($key == 'item_params') {
                            $itemData[$i][$key] = $this->getItemParams($filter['company_id'], $value);
                        } elseif ($key == 'item_spec') {
                            $itemData[$i][$key] = $this->getItemSpec($filter['company_id'], $value);
                        } elseif ($key == 'price') {
                            $itemData[$i][$key] = bcdiv($value[$key], 100, 2);
                        } elseif ($key == 'market_price') {
                            $itemData[$i][$key] = bcdiv($value[$key], 100, 2);
                        } elseif ($key == 'cost_price') {
                            $itemData[$i][$key] = bcdiv($value[$key], 100, 2);
                        } else {
                            $itemData[$i][$key] = $value[$key] ?? '';
                        }
                    }
                }
                yield $itemData;
            }
        }
    }

    /**
     * 获取商品分类
     */
    private function getItemCategory($companyId, $row, $isMain = false)
    {
        $itemsCategoryService = new ItemsCategoryService();
        if ($isMain) {
            $categoryPath = $itemsCategoryService->getCategoryPathById($row['item_main_cat_id'], $companyId, $isMain);
            $catNames = [];
            if ($categoryPath) {
                $this->getCategoryPathName($categoryPath[0], $catNames);
            }
            return implode('->', $catNames);
        } else {
            $catNamesArr = [];
            foreach (($row['item_cat_id'] ?? []) as $v) {
                $categoryPath = $itemsCategoryService->getCategoryPathById($v, $companyId, $isMain);
                $catNames = [];
                if ($categoryPath) {
                    $this->getCategoryPathName($categoryPath[0], $catNames);
                    $catNamesArr[] = implode('->', $catNames);
                }
            }
            return implode('|', $catNamesArr);
        }
    }

    /**
     * 通过运费模版ID，获取运费模版名称
     */
    private function getTemplatesName($companyId, $row)
    {
        if (!$row['templates_id']) {
            return '';
        }

        $shippingTemplatesService = new ShippingTemplatesService();
        $data = $shippingTemplatesService->getInfo($row['templates_id'], $companyId);
        return $data['name'] ?? '';
    }

    /**
     * 通过品牌ID获取品牌名称
     */
    private function getBrandName($companyId, $row)
    {
        $brandId = $row['brand_id'] ?? 0;
        $brandName = '';
        if ($brandId) {
            $itemsAttributesService = new ItemsAttributesService();
            $data = $itemsAttributesService->getInfo(['company_id' => $companyId, 'attribute_id' => $brandId, 'attribute_type' => 'brand']);
            $brandName = $data['attribute_name'] ?? '';
        }
        return $brandName;
    }

    private function getCategoryPathName($categoryPath, &$catNames)
    {
        $catNames[] = $categoryPath['category_name'];
        if ($categoryPath['children'] ?? []) {
            $this->getCategoryPathName($categoryPath['children'][0], $catNames);
        }
    }

    /**
     * 获取商品参数
     *
     * item_params: 功效:美白提亮|性别:男性
     */
    private function getItemParams($companyId, $row)
    {
        $itemsAttributesService = new ItemsAttributesService();
        $itemRelAttributesService = new ItemRelAttributesService();
        $itemRelAttributes = $itemRelAttributesService->lists(['item_id' => $row['item_id'], 'attribute_type' => 'item_params', 'company_id' => $companyId]);
        $itemParams = [];
        foreach ($itemRelAttributes['list'] as $value) {
            $attrValues = $itemsAttributesService->getAttrValuesListBy(['attribute_id' => $value['attribute_id'], 'attribute_value_id' => $value['attribute_value_id'], 'company_id' => $companyId]);
            $attr = $itemsAttributesService->getInfo(['attribute_id' => $value['attribute_id'], 'attribute_type' => 'item_params', 'company_id' => $companyId]);
            if ($attr && $attrValues['total_count'] > 0) {
                $itemParams[] = $attr['attribute_name'] . ':' . $attrValues['list'][0]['attribute_value'];
            }
        }

        return implode('|', $itemParams);
    }

    private function getItemSpec($companyId, $row)
    {
        $itemsAttributesService = new ItemsAttributesService();
        $itemRelAttributesService = new ItemRelAttributesService();
        $itemRelAttributes = $itemRelAttributesService->lists(['item_id' => $row['item_id'], 'attribute_type' => 'item_spec', 'company_id' => $companyId]);
        $itemSpec = [];
        foreach ($itemRelAttributes['list'] as $value) {
            $attrValues = $itemsAttributesService->getAttrValuesListBy(['attribute_id' => $value['attribute_id'], 'attribute_value_id' => $value['attribute_value_id'], 'company_id' => $companyId]);
            $attr = $itemsAttributesService->getInfo(['attribute_id' => $value['attribute_id'], 'attribute_type' => 'item_spec', 'company_id' => $companyId]);
            if ($attr && $attrValues['total_count'] > 0) {
                $itemSpec[] = $attr['attribute_name'] . ':' . $attrValues['list'][0]['attribute_value'];
            }
        }

        return implode('|', $itemSpec);
    }
}
