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

namespace GoodsBundle\ApiServices;

use GoodsBundle\Entities\ItemsAttributes;
use GoodsBundle\Entities\ItemsAttributeValues;
use GoodsBundle\Entities\ItemRelAttributes;
use Dingo\Api\Exception\ResourceException;

class ItemsAttributesService
{
    /**
     * @var itemsAttributesRepository
     */
    private $itemsAttributesRepository;

    /**
     * ItemsService 构造函数.
     */
    public function __construct()
    {
        $this->itemsAttributesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributes::class);
    }

    public function createAttr($data)
    {
        // is_image 只对规格属性类型有效
        $insertData = [
            'company_id' => $data['company_id'],
            'shop_id' => isset($data['shop_id']) ? $data['shop_id'] : 0,
            'attribute_type' => $data['attribute_type'],
            'attribute_name' => $data['attribute_name'],
            'attribute_memo' => isset($data['attribute_memo']) ? $data['attribute_memo'] : '',
            'attribute_sort' => isset($data['attribute_sort']) ? $data['attribute_sort'] : 1,
            'distributor_id' => isset($data['distributor_id']) ? $data['distributor_id'] : 0,
            'is_show' => isset($data['is_show']) ? $data['is_show'] : 'true',
            'is_image' => isset($data['is_image']) ? $data['is_image'] : 'true',
            'image_url' => isset($data['image_url']) ? $data['image_url'] : '',
            'attribute_show' => isset($data['attribute_show']) ? $data['attribute_show'] : 'select'
        ];

        if (isset($data['attribute_values']) && count($data['attribute_values']) > 60) {
            throw new ResourceException('参数或规格值不能超过60个');
        }

        if (isset($data['attribute_values']) && count($data['attribute_values']) <= 0 && $data['is_show'] == 'true') {
            throw new ResourceException('请添加参数或者规格值');
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // item_params 和 item_spec 类型会有相同的值
            $result = $this->itemsAttributesRepository->create($insertData);
            if (isset($data['attribute_values']) && $data['attribute_values']) {
                $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
                $attributeValueName = [];
                foreach ($data['attribute_values'] as $key => $value) {
                    if (isset($attributeValueName[$value['attribute_value']])) {
                        throw new ResourceException('参数或者规格值不能重复');
                    } else {
                        $attributeValueName[$value['attribute_value']] = true;
                    }
                    if (!$value['attribute_value']) {
                        throw new ResourceException('参数值不能为空');
                    }
                    $attributeValues = [
                        'attribute_id' => $result['attribute_id'],
                        'company_id' => $data['company_id'],
                        'shop_id' => isset($data['shop_id']) ? $data['shop_id'] : 0,
                        'attribute_value' => trim($value['attribute_value']),
                        'sort' => $key,
                        'image_url' => isset($value['image_url']) ? $value['image_url'] : '',
                    ];
                    $itemsAttributesValuesRepository->create($attributeValues);
                }
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
        return true;
    }

    public function deleteAttr($filter)
    {
        $itemRelAttributesRepository = app('registry')->getManager('default')->getRepository(ItemRelAttributes::class);
        $relItemsData = $itemRelAttributesRepository->getInfo($filter);
        if ($relItemsData) {
            throw new ResourceException('有关联商品，请先处理关联的商品');
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->itemsAttributesRepository->deleteBy($filter);

            $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
            $itemsAttributesValuesRepository->deleteBy($filter);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return true;
    }

    /**
     * 处理更新的规格值
     */
    private function __preUpdateAttrValues($filter, $data)
    {
        $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
        $attrValueslists = $itemsAttributesValuesRepository->lists($filter);

        $oldAttributeValueIds = [];
        if ($attrValueslists['total_count'] > 0) {
            $oldAttributeValueIds = array_column($attrValueslists['list'], 'attribute_value_id');
            $newAttributeValueIds = array_column($data['attribute_values'], 'attribute_value_id');
            $deleteIds = array_diff($oldAttributeValueIds, $newAttributeValueIds);
            if (!$deleteIds) {
                return true;
            }
            $itemRelAttributesRepository = app('registry')->getManager('default')->getRepository(ItemRelAttributes::class);
            $relItemsData = $itemRelAttributesRepository->getInfo(['attribute_value_id' => $deleteIds, 'attribute_id' => $filter['attribute_id']]);
            if ($relItemsData) {
                throw new ResourceException('数值有关联商品，请先处理关联的商品');
            }
            // 删除老数据
            $itemsAttributesValuesRepository->deleteBy(['attribute_value_id' => $deleteIds, 'attribute_id' => $filter['attribute_id'], 'company_id' => $filter['company_id']]);
        }

        return true;
    }

    /**
     * 更新商品属性
     */
    public function updateAttr($filter, $data)
    {
        $info = $this->itemsAttributesRepository->getInfo($filter);
        if (!$info) {
            throw new ResourceException('更新的数据不存在');
        }

        $result = $this->itemsAttributesRepository->updateOneBy($filter, $data);
        // 如果是品牌则不需要进行处理其他
        if ($info['attribute_type'] == 'brand') {
            return true;
        }

        if (isset($data['attribute_values']) && count($data['attribute_values']) > 60) {
            throw new ResourceException('参数或规格值不能超过60个');
        }

        if ((!isset($data['attribute_values']) || count($data['attribute_values']) === 0) && $data['is_show'] == 'true') {
            throw new ResourceException('请添加参数或者规格值');
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // item_params 和 item_spec 类型会有相同的值

            $this->__preUpdateAttrValues($filter, $data);

            $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
            $attributeValueName = [];
            if ($data['attribute_values']) {
                foreach ($data['attribute_values'] as $key => $value) {
                    if (isset($attributeValueName[$value['attribute_value']])) {
                        throw new ResourceException('参数或者规格值不能重复');
                    } else {
                        $attributeValueName[$value['attribute_value']] = true;
                    }
                    if (!$value['attribute_value']) {
                        throw new ResourceException('参数值不能为空');
                    }
                    $attributeValues = [
                        'attribute_id' => $result['attribute_id'],
                        'company_id' => $result['company_id'],
                        'shop_id' => $result['shop_id'],
                        'attribute_value' => trim($value['attribute_value']),
                        'sort' => $key,
                        'image_url' => isset($value['image_url']) ? $value['image_url'] : '',
                        'updated' => time(),
                    ];

                    if (isset($value['attribute_value_id'])) {
                        $itemsAttributesValuesRepository->updateOneBy(['attribute_value_id' => $value['attribute_value_id'], 'company_id' => $result['company_id']], $attributeValues);
                    } else {
                        $itemsAttributesValuesRepository->create($attributeValues);
                    }
                }
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return true;
    }

    /**
     * 获取商品属性列表
     */
    public function getAttrList($filter, $page = 1, $pageSize = 20, $orderBy = array())
    {
        $lists = $this->itemsAttributesRepository->lists($filter, $page, $pageSize, $orderBy);
        if ($lists['total_count'] > 0) {
            // 后续优化
            $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
            foreach ($lists['list'] as $key => $row) {
                if ($row['attribute_type'] == 'item_spec' || $row['attribute_type'] == 'item_params') {
                    $lists['list'][$key]['attribute_values'] = $itemsAttributesValuesRepository->lists(['attribute_id' => $row['attribute_id']], 1, 100);
                }
            }
        }

        return $lists;
    }

    public function getAttrValuesListBy($filter)
    {
        $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
        return $itemsAttributesValuesRepository->lists($filter, 1, 100);
    }

    /**
     * 获取单个商品属性值
     */
    public function getItemsAttrValuesList($attributeId, $companyId)
    {
        $info = $this->itemsAttributesRepository->getInfo(['company_id' => $companyId, 'attribute_id' => $attributeId]);
        if ($info) {
            $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
            $info['attribute_values'] = $itemsAttributesValuesRepository->lists(['company_id' => $companyId, 'attribute_id' => $attributeId]);
        }

        return $info;
    }

    /**
     * 获取属性数据和对应的属性值
     */
    public function getAttrValuesList($attributeIds, $attributeValueIds, $bindData)
    {
        $attributeList = $this->itemsAttributesRepository->lists(['attribute_id' => $attributeIds], 1, -1);
        if ($attributeList['total_count'] <= 0) {
            return [];
        }
        $attributeList = array_column($attributeList['list'], null, 'attribute_id');

        $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
        $attrValuesList = $itemsAttributesValuesRepository->lists(['attribute_value_id' => $attributeValueIds], 1, -1);
        if ($attrValuesList['total_count'] <= 0) {
            return [];
        }

        $attrValuesList = array_column($attrValuesList['list'], null, 'attribute_value_id');

        $result = [];
        foreach ($bindData as $attributeId => $attributeValuesIds) {
            $attributeValues = [];
            foreach ($attributeValuesIds as $attributeValueId) {
                if (isset($attrValuesList[$attributeValueId])) {
                    $attributeValues[] = [
                        'attribute_value_id' => $attrValuesList[$attributeValueId]['attribute_value_id'],
                        'attribute_value_name' => $attrValuesList[$attributeValueId]['attribute_value'],
                    ];
                }
            }

            if (isset($attributeList[$attributeId]) && $attributeValues) {
                $result[] = [
                    'attribute_id' => $attributeId,
                    'attribute_name' => $attributeList[$attributeId]['attribute_name'],
                    'attribute_values' => $attributeValues,
                ];
            }
        }

        return $result;
    }

    /**
     * 获取商品关联的属性值
     */
    public function getItemsRelAttrValuesList($data)
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

        $attrList = $this->itemsAttributesRepository->lists(['attribute_id' => $attributeIds], 1, -1);
        $attrListNew = array_column($attrList['list'], null, 'attribute_id');

        $itemsAttributesValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
        $attrValuesList = $itemsAttributesValuesRepository->lists(['attribute_value_id' => $attributeValuesIds, 'attribute_value_id|neq' => null], 1, -1);
        $attrValuesListNew = array_column($attrValuesList['list'], null, 'attribute_value_id');

        $itemSpecDesc = [];
        $itemParams = [];
        foreach ($data as $row) {
            if ($row['attribute_type'] == 'brand') {
                $return['brand']['brand_id'] = $row['attribute_id'];
                $return['brand']['goods_brand'] = $attrListNew[$row['attribute_id']]['attribute_name'];
                $return['brand']['brand_logo'] = $row['image_url'];
            } else {
                $return['attribute_ids'][] = $row['attribute_id'];
            }

            if ($row['attribute_type'] == 'item_params' && ($row['attribute_value_id'] || $itemParamsCustomName[$row['attribute_id']])) {
                $oldAttributeValueName = isset($attrValuesListNew[$row['attribute_value_id']]) ? $attrValuesListNew[$row['attribute_value_id']]['attribute_value'] : '';
                $attributeValueName = $itemParamsCustomName[$row['attribute_id']] ?: $oldAttributeValueName;
                $itemParams[$row['attribute_id']] = [
                    'attribute_id' => $row['attribute_id'],
                    'attribute_name' => $attrListNew[$row['attribute_id']]['attribute_name'],
                    'attribute_value_id' => $row['attribute_value_id'],
                    'attribute_value_name' => $attributeValueName,
                ];
            }

            if ($row['attribute_type'] == 'item_spec') {
                $itemSpecDesc[$row['attribute_id']]['spec_id'] = $row['attribute_id'];
                $itemSpecDesc[$row['attribute_id']]['spec_name'] = $attrListNew[$row['attribute_id']]['attribute_name'];
                $itemSpecDesc[$row['attribute_id']]['is_image'] = ($attrListNew[$row['attribute_id']]['is_image'] == 'true') ? true : false;
                $itemSpecDesc[$row['attribute_id']]['spec_values'][$row['attribute_value_id']] = [
                    'spec_value_id' => $row['attribute_value_id'],
                    'spec_custom_value_name' => $attributeValuesCustomName[$row['attribute_value_id']] ?: null,
                    'spec_value_name' => $attributeValuesCustomName[$row['attribute_value_id']] ?: $attrValuesListNew[$row['attribute_value_id']]['attribute_value'],
                    'item_image_url' => $attributeValuesImgs[$row['attribute_value_id']] ?? '',
                    'spec_image_url' => $attrValuesListNew[$row['attribute_value_id']]['image_url'],
                ];
                //商品详情页规格图片默认优先显示自定义规格，然后是规格图片
                if (isset($attributeValuesImgs[$row['attribute_value_id']]) && $attributeValuesImgs[$row['attribute_value_id']]) {
                    $itemSpecDesc[$row['attribute_id']]['spec_values'][$row['attribute_value_id']]['spec_image_url'] = reset($attributeValuesImgs[$row['attribute_value_id']]);
                }
                $return['item_spec'][$row['item_id']][$row['attribute_id']] = [
                    'item_id' => $row['item_id'],
                    'spec_id' => $row['attribute_id'],
                    'spec_value_id' => $row['attribute_value_id'],
                    'spec_name' => $attrListNew[$row['attribute_id']]['attribute_name'],
                    'spec_custom_value_name' => $attributeValuesCustomName[$row['attribute_value_id']] ?: null,
                    'spec_value_name' => $attributeValuesCustomName[$row['attribute_value_id']] ?: $attrValuesListNew[$row['attribute_value_id']]['attribute_value'],
                    'item_image_url' => $attributeValuesImgs[$row['attribute_value_id']] ?? '',
                    'spec_image_url' => $attrValuesListNew[$row['attribute_value_id']]['image_url'],
                ];
                //加入购物车规格图片默认优先显示自定义规格，然后是规格图片
                if (isset($attributeValuesImgs[$row['attribute_value_id']]) && $attributeValuesImgs[$row['attribute_value_id']]) {
                    $return['item_spec'][$row['item_id']][$row['attribute_id']]['spec_image_url'] = reset($attributeValuesImgs[$row['attribute_value_id']]);
                }
            }
        }

        if ($itemParams) {
            foreach ($itemParams as $itemParamsRow) {
                $return['item_params'][] = $itemParamsRow;
            }
        }

        if ($itemSpecDesc) {
            $tempItemSpecDesc = [];
            $specImages = [];
            foreach ($itemSpecDesc as $value) {
                rsort($value['spec_values']);
                if ($value['is_image']) {
                    $specImages = $value['spec_values'];
                }
                $tempItemSpecDesc[] = $value;
            }
            $return['item_spec_desc'] = $tempItemSpecDesc;
            $return['spec_images'] = $specImages;
        }

        return $return;
    }

    public function getBrandList($filter)
    {
        // 根据商品分类id，获取到对应的商品ID
        $itemsService = new ItemsService();
        $itemIds = [];
        if (isset($filter['category_id']) && $filter['category_id']) {
            $categoryFilter = [
                'company_id' => $filter['company_id'],
                'category_id' => $filter['category_id'],
            ];
            $itemIds = $itemsService->getItemIdsByCategoryId($categoryFilter);
            if ($itemIds == -1 || !$itemIds) {
                return [
                    'total_count' => 0,
                    'list' => []
                ];
            }
            unset($filter['category_id']);
        }
        $newfilter = [
            'company_id' => $filter['company_id'],
        ];
//        if ($filter['distributor_id'] ?? 0) {
//            $newfilter['distributor_id'] = $filter['distributor_id'];
//        }
        if ($itemIds) {
            $newfilter['item_id'] = $itemIds;
        }
        if ($filter['item_name'] ?? 0) {
            $newfilter['item_name|contains'] = $filter['item_name'];
        }
        $brandList = $itemsService->getBrandIds($newfilter);
        $brandFilter = [
            'company_id' => $filter['company_id'],
                'attribute_type' => 'brand',
            'attribute_id' => array_column($brandList, 'brand_id'),
        ];
        $itemSelectList['brand_list'] = $this->itemsAttributesRepository->lists($brandFilter, 1, -1);
        return $itemSelectList;
    }

    public function __call($method, $parameters)
    {
        return $this->itemsAttributesRepository->$method(...$parameters);
    }
}
