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

namespace OpenapiBundle\Services\Items;

use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Constants\ErrorCode;

use GoodsBundle\Entities\Items;
use OpenapiBundle\Services\BaseService;
use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsService as GoodsItemsService;
use GoodsBundle\Services\ItemStoreService;

class ItemsService extends BaseService
{
    public function getEntityClass(): string
    {
        // ShopEx EcShopX Service Component
        return Items::class;
    }

    /**
     * 格式化会员标签分类列表数据
     * @param  array $dataList 会员标签分类列表数据
     * @param  int $page     当前页数
     * @param  int $pageSize 每页条数
     * @return array           处理后的列表数据
     */
    public function formateItemBrandList($dataList, int $page, int $pageSize)
    {
        $result = $this->handlerListReturnFormat($dataList, $page, $pageSize);

        if (!$dataList['list']) {
            return $result;
        }
        $result['list'] = [];
        foreach ($dataList['list'] as $list) {
            $result['list'][] = [
                'brand_id' => $list['attribute_id'],
                'brand_name' => $list['attribute_name'],
                'image_url' => $list['image_url'],
                'created' => date('Y-m-d H:i:s', $list['created']),
                'updated' => date('Y-m-d H:i:s', $list['updated']),
            ];
        }
        return $result;
    }

    /**
     * 格式化商品分类列表数据
     * @param  array $dataList 商品分类列表数据
     * @return array           处理后的商品分类列表数据
     */
    public function formateCategoryList($dataList)
    {
        $result = [];
        if (empty($dataList)) {
            return $result;
        }
        foreach ($dataList as $key => $list) {
            $result[$key] = $this->formateCategory($list);
        }
        return $result;
    }

    /**
     * 格式化商品分类数据
     * @param  array $category 商品分类数据
     * @return array           处理后的商品分类数据
     */
    private function formateCategory($category)
    {
        // NOTE: important business logic
        $_category = [
            'category_id' => $category['category_id'],
            'category_name' => $category['category_name'],
            'category_level' => $category['category_level'],
            'parent_id' => $category['parent_id'],
            'path' => $category['path'],
            'sort' => $category['sort'],
            'image_url' => $category['image_url'],
            'created' => date('Y-m-d H:i:s', $category['created']),
            'updated' => date('Y-m-d H:i:s', $category['updated']),
        ];
        if (isset($category['children']) && $category['children']) {
            foreach ($category['children'] as $key => $children) {
                $_category['children'][$key] = $this->formateCategory($children);
            }
        }
        return $_category;
    }

    /**
     * 根据三级的主类目名称，获取商品主类目详情、规格、属性
     * @param  string $companyId    企业ID
     * @param  string $categoreName 类目名称 一级类目->二级类目->三级类目
     * @return array               商品主类目详情数据
     */
    public function getMainCategoryDetail($companyId, $categoreName)
    {
        $name = explode('->', $categoreName);
        if (count($name) != 3) {
            throw new ErrorException(ErrorCode::GOODS_MAINCATEGORY_ERROR, '主类目名称格式错误');
        }

        $itemsCategoryService = new ItemsCategoryService();
        $filter = [
            'company_id' => $companyId,
            'is_main_category' => true,
            'category_name' => $name[0],
        ];
        // $firstCategory = $itemsCategoryService->getCategoryInfo($filter);
        // public function lists($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1)
        $orderBy = ["created" => "DESC"];
        $firstCategory = $itemsCategoryService->lists($filter, $orderBy, -1);
        if (!$firstCategory['list']) {
            throw new ErrorException(ErrorCode::GOODS_MAINCATEGORY_NOT_FOUND, '一级类目名称未查询到相关数据');
        }
        // $filter['parent_id'] = $firstCategory['category_id'];
        $filter['parent_id'] = array_column($firstCategory['list'], 'category_id');
        $filter['category_name'] = $name[1];
        // $secondCategory = $itemsCategoryService->getCategoryInfo($filter);
        $secondCategory = $itemsCategoryService->lists($filter, $orderBy, -1);
        if (!$secondCategory['list']) {
            throw new ErrorException(ErrorCode::GOODS_MAINCATEGORY_NOT_FOUND, '二级类目名称未查询到相关数据');
        }
        // $filter['parent_id'] = $secondCategory['category_id'];
        $filter['parent_id'] = array_column($secondCategory['list'], 'category_id');
        $filter['category_name'] = $name[2];
        $threeCategory = $itemsCategoryService->lists($filter, $orderBy, 1, 1);
        // $threeCategory = $itemsCategoryService->getCategoryInfo($filter);
        if (!$threeCategory['list']) {
            throw new ErrorException(ErrorCode::GOODS_MAINCATEGORY_NOT_FOUND, '三级类目名称未查询到相关数据');
        }
        $filter = [
            'company_id' => $companyId,
            'category_id' => array_column($threeCategory['list'], 'category_id'),
            // 'category_id' => $threeCategory['category_id'],
            'is_main_category' => true,
        ];
        // $categoryInfo = $itemsCategoryService->getCategoryInfo($filter);
        $categoryList = $itemsCategoryService->lists($filter, $orderBy, 1, 1);
        if (!$categoryList['list']) {
            return [];
        }
        $filter = [
            'company_id' => $companyId,
            'category_id' => $categoryList['list'][0]['category_id'],
            // 'category_id' => $threeCategory['category_id'],
            'is_main_category' => true,
        ];
        $categoryInfo = $itemsCategoryService->getCategoryInfo($filter);
        $result = $this->formateCategoryInfo($categoryInfo);
        return $result;
    }

    /**
     * 格式化商品主类目数据
     * @param  array $categoryInfo 商品主类目数据
     * @return array               处理后的商品主类目数据
     */
    private function formateCategoryInfo($categoryInfo)
    {
        $result = [
            'category_id' => $categoryInfo['category_id'],
            'category_name' => $categoryInfo['category_name'],
            'sort' => $categoryInfo['sort'],
            'category_level' => $categoryInfo['category_level'],
            'path' => $categoryInfo['path'],
            'image_url' => $categoryInfo['image_url'],
            'created' => date('Y-m-d H:i:s', $categoryInfo['created']),
            'updated' => date('Y-m-d H:i:s', $categoryInfo['updated']),
        ];
        if (isset($categoryInfo['goods_params']) && $categoryInfo['goods_params']) {
            $result['goods_params'] = $this->formateAttribute($categoryInfo['goods_params']);
        }
        if (isset($categoryInfo['goods_spec']) && $categoryInfo['goods_spec']) {
            $result['goods_spec'] = $this->formateAttribute($categoryInfo['goods_spec']);
        }
        return $result;
    }

    /**
     * 格式化商品主类目属性
     * @param  array $goodsAttribute 属性列表
     * @return array                 格式化后的属性列表
     */
    private function formateAttribute($goodsAttribute)
    {
        foreach ($goodsAttribute as $attribute) {
            $result[] = [
                'attribute_id' => $attribute['attribute_id'],
                'attribute_name' => $attribute['attribute_name'],
                'attribute_memo' => $attribute['attribute_memo'],
                'attribute_sort' => $attribute['attribute_sort'],
                'is_show' => $attribute['is_show'],
                'is_image' => $attribute['is_image'],
                // 'image_url' => $attribute['image_url'],
                'created' => date('Y-m-d H:i:s', $attribute['created']),
                'updated' => date('Y-m-d H:i:s', $attribute['updated']),
                'attribute_values' => $this->formateAttributeValues($attribute['attribute_values']),
            ];
        }
        return $result;
    }

    /**
     * 格式化属性值
     * @param  array $attributeValues 属性值列表
     * @return array                  格式化后的属性值列表
     */
    private function formateAttributeValues($attributeValues)
    {
        $result['total_count'] = $attributeValues['total_count'];
        foreach ($attributeValues['list'] as $values) {
            $result['list'][] = [
                'attribute_value_id' => $values['attribute_value_id'],
                'attribute_id' => $values['attribute_id'],
                'attribute_value' => $values['attribute_value'],
                'sort' => $values['sort'],
                'image_url' => $values['image_url'],
                'created' => date('Y-m-d H:i:s', $values['created']),
                'updated' => date('Y-m-d H:i:s', $values['updated']),
            ];
        }
        return $result;
    }

    /**
     * 格式化商品列表数据
     * @param  string $companyId 企业ID
     * @param  array $dataList  商品列表数据
     * @param  int $page      当前页数
     * @param  int $pageSize  每页条数
     * @return array
     */
    public function formateItemsList($companyId, $dataList, int $page, int $pageSize)
    {
        $result = $this->handlerListReturnFormat($dataList, $page, $pageSize);

        if (!$dataList['list']) {
            return $result;
        }
        $result['list'] = [];
        $item_cat_id = [];
        foreach ($dataList['list'] as $list) {
            if ($item_cat_id) {
                $item_cat_id = array_merge($item_cat_id, $list['item_cat_id']);
            } else {
                $item_cat_id = $list['item_cat_id'];
            }
        }
        $itemsCategoryService = new ItemsCategoryService();
        $filter = [
            'company_id' => $companyId,
            'category_id' => $item_cat_id,
        ];
        $orderBy = ['created' => 'desc'];
        $categoryList = $itemsCategoryService->lists($filter, $orderBy, -1, 1);
        $_categoryList = array_column($categoryList['list'], null, 'category_id');
        foreach ($dataList['list'] as $list) {
            $_list = [
                'item_id' => $list['item_id'],
                'item_bn' => $list['item_bn'],
                'item_name' => $list['item_name'],
                'price' => $list['price'],
                'store' => $list['store'],
                'approve_status' => $list['approve_status'],
                'nospec' => $list['nospec'],
                'is_self' => $list['distributor_id'] == 0,
                'update_time' => $list['updated'],
            ];
            foreach ($list['item_cat_id'] as $category_id) {
                $_list['category_name'][] = '['.$_categoryList[$category_id]['category_name'].']' ?? '';
            }
            $result['list'][] = $_list;
        }
        return $result;
    }

    /**
     * 根据is_default=true的商品货号，获取商品详情数据
     * @param  string $companyId 企业ID
     * @param  string $itemBn    商品货号
     * @return array            商品详情数据
     */
    public function getItemSpuDetail($companyId, $itemBn)
    {
        $goodsItemsService = new GoodsItemsService();
        $filter = [
            'company_id' => $companyId,
            'item_bn' => $itemBn,
            'is_default' => true,
        ];
        $itemInfo = $goodsItemsService->getInfo($filter);
        if (!$itemInfo) {
            throw new ErrorException(ErrorCode::GOODS_NOT_FOUND);
        }
        $spuDetail = $goodsItemsService->getItemsDetail($itemInfo['item_id'], null, [], $companyId);
        $result = $this->formateItemSpuDetail($spuDetail);
        return $result;
    }

    /**
     * 格式化商品详情数据
     * @param  array $data 商品详情数据
     * @return array
     */
    public function formateItemSpuDetail($data)
    {
        $result = [
            'goods_id' => $data['goods_id'],
            'item_bn' => $data['item_bn'],
            'item_name' => $data['item_name'],
            'brief' => $data['brief'],
            'item_unit' => $data['item_unit'],
            'sort' => $data['sort'],
            'brand_id' => $data['brand_id'],
            'templates_id' => $data['templates_id'],
            'is_gift' => $data['is_gift'],
            'pics' => $data['pics'],
            'nospec' => $data['nospec'],
            'is_show_specimg' => $data['is_show_specimg'],
            'weight' => $data['weight'],
            'volume' => $data['volume'],
            'price' => $data['price'],
            'market_price' => $data['market_price'],
            'cost_price' => $data['cost_price'],
            'barcode' => $data['barcode'],
            'approve_status' => $data['approve_status'],
            'store' => $data['store'],
            'item_main_cat_id' => $data['item_main_cat_id'],
            'item_category' => $data['item_category'],
            'update_time' => $data['updated'],
        ];
        if ($data['nospec'] === false || $data['nospec'] === 'false') {
            $result['spec_items'] = $this->formateItemSpec($data['spec_items']);
            $result['item_spec_desc'] = $this->formateItemSpecDesc($data['item_spec_desc']);
            $result['spec_images'] = $this->formateSpecImages($data['spec_images']);
        }
        return $result;
    }

    /**
     * 格式化商品规格图片数据
     * @param  array $specImages 商品规格数据
     * @return [type]             [description]
     */
    private function formateSpecImages($specImages)
    {
        $result = [];
        foreach ($specImages as $images) {
            $result[] = [
                'spec_value_id' => $images['spec_value_id'],
                'spec_custom_value_name' => $images['spec_custom_value_name'],
                'spec_value_name' => $images['spec_value_name'],
                'spec_image_url' => $images['spec_image_url'],
            ];
        }
        return $result;
    }

    private function formateItemSpecDesc($itemSpecDesc)
    {
        $result = [];
        foreach ($itemSpecDesc as $spec) {
            $result[] = [
                'spec_id' => $spec['spec_id'],
                'spec_name' => $spec['spec_name'],
                'is_image' => $spec['is_image'],
                'spec_values' => $this->formateSpecValues($spec['spec_values']),
            ];
        }
        return $result;
    }

    private function formateSpecValues($specValues)
    {
        $result = [];
        foreach ($specValues as $values) {
            $result[] = [
                'spec_value_id' => $values['spec_value_id'],
                'spec_value_name' => $values['spec_value_name'],
                'spec_custom_value_name' => $values['spec_custom_value_name'],
                'spec_image_url' => $values['spec_image_url'],
            ];
        }
        return $result;
    }

    private function formateItemSpec($specItems)
    {
        $result = [];
        foreach ($specItems as $spec) {
            $result[] = [
                'item_bn' => $spec['item_bn'],
                'is_default' => $spec['is_default'],
                'approve_status' => $spec['approve_status'],
                'weight' => $spec['weight'],
                'volume' => $spec['volume'],
                'price' => $spec['price'],
                'market_price' => $spec['market_price'],
                'cost_price' => $spec['cost_price'],
                'barcode' => $spec['barcode'],
                'item_spec' => $this->formateSpec($spec['item_spec']),
                'store' => $spec['store'],
            ];
        }
        return $result;
    }

    private function formateSpec($spec)
    {
        $result = [];
        foreach ($spec as $data) {
            $result[] = [
                'spec_id' => $data['spec_id'],
                'spec_value_id' => $data['spec_value_id'],
                'spec_name' => $data['spec_name'],
                'spec_custom_value_name' => $data['spec_custom_value_name'],
                'spec_value_name' => $data['spec_value_name'],
                'spec_image_url' => $data['spec_image_url'],
            ];
        }
        return $result;
    }

    /**
     * 根据is_default=true的商品item_bn，批量处理商品的上架、下架的状态更改
     * @param  string $companyId 企业ID
     * @param  array $itemBn    商品货号
     * @param  string $status    商品状态
     * @return bool
     */
    public function batchUpdateItemsStatus($companyId, $itemBn, $status)
    {
        $goodsItemsService = new GoodsItemsService();
        $filter = [
            'company_id' => $companyId,
            'item_bn' => $itemBn,
            'is_default' => true,
        ];

        $goods_id = $goodsItemsService->getItemsLists($filter, 'goods_id');
        if (!$goods_id) {
            throw new ErrorException(ErrorCode::GOODS_NOT_FOUND);
        }
        $result = $goodsItemsService->updateItemsStatus($companyId, $goods_id, $status);
        return $result;
    }

    /**
     * 根据is_default=true的商品货号，删除单个商品
     * @param  string $companyId 企业ID
     * @param  string $itemBn    商品货号
     * @return bool
     */
    public function deleteItems($companyId, $itemBn)
    {
        $goodsItemsService = new GoodsItemsService();
        $filter = [
            'company_id' => $companyId,
            'item_bn' => $itemBn,
            'is_default' => true,
        ];
        $itemInfo = $goodsItemsService->getInfo($filter);
        if (!$itemInfo) {
            throw new ErrorException(ErrorCode::GOODS_NOT_FOUND);
        }
        $params = [
            'company_id' => $companyId,
            'item_id' => $itemInfo['item_id'],
            'distributor_id' => 0,
        ];
        return $goodsItemsService->deleteItems($params);
    }

    /**
     * 更新库存信息
     * @param array $filter 过滤条件
     * @param int $store 库存值
     * @param bool $isCover true表示为覆盖操作，false为对原库存做自增/自减（基于store的值来决定）
     * @return int 受影响的行数
     */
    public function updateStore(array $filter, int $store, bool $isCover): int
    {
        $item = $this->find($filter);
        if (empty($item)) {
            throw new ErrorException(ErrorCode::GOODS_NOT_FOUND);
        }

        if ($isCover) {
            $updateData = ["store" => $store];
        } else {
            if ($store > 0) {
                $updateData = ["store" => sprintf("store + %d", $store)];
            } elseif ($store < 0) {
                // 取绝对值
                $store = abs($store);
                $filter["store|gte"] = $store;
                $updateData = ["store" => sprintf("store - %d", $store)];
            } else {
                return 0;
            }
        }

        $itemStoreService = new ItemStoreService();
        $result = (int)$this->getRepository()->updateBy($filter, $updateData, false);
        if ($result) {
            $list = $this->getRepository()->getLists($filter, 'item_id,store', 1, -1);
            foreach ($list as $row) {
                $itemStoreService->saveItemStore($row['item_id'], $row['store']);
            }
        }

        return $result;
    }

    /**
     * 更新价格信息
     * @param array $filter 过滤条件
     * @param array $params 价格
     * @return int 受影响的行数
     */
    public function updatePrice(array $filter, $params = null): int
    {
        $item = $this->find($filter);
        if (empty($item)) {
            throw new ErrorException(ErrorCode::GOODS_NOT_FOUND);
        }

        $updateData = [];
        if (isset($params['price'])) {
            $updateData['price'] = (int)bcmul($params['price'], 100, 0);
        }

        if (isset($params['market_price'])) {
            $updateData['market_price'] = (int)bcmul($params['market_price'], 100, 0);
        }

        if (isset($params['cost_price'])) {
            $updateData['cost_price'] = (int)bcmul($params['cost_price'], 100, 0);
        }

        if (!$updateData) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, '应至少指定一个要更新的价格');
        }

        return (int)$this->getRepository()->updateBy($filter, $updateData, false);
    }
}
