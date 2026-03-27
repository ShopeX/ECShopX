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

namespace GoodsBundle\Services;

use GoodsBundle\Entities\Items;
use EspierBundle\Services\BusService;
use GoodsBundle\Entities\ItemsCategory;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Entities\ItemRelAttributes;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use Doctrine\DBAL\Connection;
use PointsmallBundle\Entities\PointsmallItemRelAttributes;
use PointsmallBundle\Entities\PointsmallItems;

class ItemsCategoryService
{
    use MagicLangTrait;
    /**
     * @var \GoodsBundle\Repositories\ItemsCategoryRepository
     */
    public $itemsCategoryRepository;

    /**
     * ItemsService 构造函数.
     */
    public function __construct()
    {
        $this->itemsCategoryRepository = app('registry')->getManager('default')->getRepository(ItemsCategory::class);
    }

    /**
     * 获取所有分类
     */
    public function getItemsCategory($filter, $isShow = true, $page = 1, $pageSize = -1, $orderBy = ["sort" => "DESC", "created" => "ASC"], $columns = "*", $profit = 1)
    {
        $data['filter'] = $filter;
        $data['is_show'] = $isShow;
        $data['page'] = $page;
        $data['page_size'] = $pageSize;
        $data['order_by'] = $orderBy;
        $data['columns'] = $columns;
        $data['country_code']= $this->getLang();
        $result = BusService::instance('goods')->post('/service/goods/category/list', $data);
        if ($result && (isset($filter['is_main_category']) && $filter['is_main_category'])) {
            $itemsCategoryProfitService = new ItemsCategoryProfitService();
            $categoryIds = [];
            foreach ($result as $v1) {
                if (isset($filter['parent_id']) || isset($filter['category_level'])) {
                    if ($v1['category_level'] == 3) {
                        $categoryIds[] = $v1['category_id'];
                    }
                } else {
                    if ($v1['children'] ?? 0) {
                        foreach ($v1['children'] as $v2) {
                            if ($v2['children'] ?? 0) {
                                foreach ($v2['children'] as $v3) {
                                    $categoryIds[] = $v3['category_id'];
                                }
                            }
                        }
                    }
                }
            }

            if ($profit) {
                if ($categoryIds) {
                    //获取分销价格
                    $itemsCategoryProfitList = $itemsCategoryProfitService->lists(['category_id' => $categoryIds, 'company_id' => $filter['company_id']]);
                    $itemsCategoryProfitList = array_column($itemsCategoryProfitList['list'], null, 'category_id');
                }
                foreach ($result as &$v1) {
                    if (isset($filter['parent_id']) || isset($filter['category_level'])) {
                        if ($v1['category_level'] == 3) {
                            if (!isset($itemsCategoryProfitList[$v1['category_id']])) {
                                continue;
                            }
                            $v1['profit_type'] = (int)(isset($itemsCategoryProfitList[$v1['category_id']]) ? $itemsCategoryProfitList[$v1['category_id']]['profit_type'] : 0);
                            $profitConf = isset($itemsCategoryProfitList[$v1['category_id']]) ? json_decode($itemsCategoryProfitList[$v1['category_id']]['profit_conf'], 1) : [];
                            $v1['profit_conf_profit'] = $profitConf['profit'];
                            $v1['profit_conf_popularize_profit'] = $profitConf['popularize_profit'];
                        }
                    } else {
                        if ($v1['children'] ?? 0) {
                            foreach ($v1['children'] as &$v2) {
                                if ($v2['children'] ?? 0) {
                                    foreach ($v2['children'] as &$v3) {
                                        if (!isset($itemsCategoryProfitList[$v3['category_id']])) {
                                            continue;
                                        }
                                        $v3['profit_type'] = (int)(isset($itemsCategoryProfitList[$v3['category_id']]) ? $itemsCategoryProfitList[$v3['category_id']]['profit_type'] : 0);
                                        $profitConf = isset($itemsCategoryProfitList[$v3['category_id']]) ? json_decode($itemsCategoryProfitList[$v3['category_id']]['profit_conf'], 1) : [];
                                        $v3['profit_conf_profit'] = $profitConf['profit'];
                                        $v3['profit_conf_popularize_profit'] = $profitConf['popularize_profit'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result;
        // $itemsCategoryInfo = $this->itemsCategoryRepository->lists($filter, $orderBy, $pageSize, $page);
        // return $this->getTree($itemsCategoryInfo['list'], 0, 0, $isShow);
    }

    public function isSaleableCategoryFilterEnabled(int $companyId): bool
    {
        if ($companyId <= 0) {
            return false;
        }
        $redis = app('redis')->connection('default');
        $value = $redis->get($this->getSaleableCategoryFilterKey($companyId));
        return (string)$value === '1';
    }

    public function setSaleableCategoryFilterEnabled(int $companyId, bool $enabled): bool
    {
        if ($companyId <= 0) {
            return false;
        }
        $redis = app('redis')->connection('default');
        return (bool)$redis->set($this->getSaleableCategoryFilterKey($companyId), $enabled ? '1' : '0');
    }

    private function getSaleableCategoryFilterKey(int $companyId): string
    {
        return 'goods:category:saleable_filter:' . $companyId;
    }

    /**
     * 关于节点的回溯
     * @param $categories
     * @return array
     */
    public function removeNoneCategories($categories): array
    {
        return $this->removeNoneCategory($this->removeNoneCategory($this->removeNoneCategory($categories), 2), 1);
    }

    /**
     * 删除商品类目中为空的节点, 效率问题这里可以改成&
     * @param $categories
     * @param int $level 指定遍历的 category_level
     * @return array
     */
    private function removeNoneCategory($categories, int $level = 0): array
    {
        foreach ($categories as $k => $cate) {
            if ($level && isset($cate['category_level']) && $cate['category_level'] > $level) {
                continue;
            }
            if (!$cate) {
                unset($categories[$k]);
                continue;
            }
            if (!isset($cate['children'])) {
                continue;
            }
            if (!$cate['children']) {
                unset($categories[$k]);
            } else {
                $categories[$k]['children'] = $this->removeNoneCategory($cate['children'], $level);
            }
        }
        return array_values($categories);
    }

    public function processingParams(&$filter)
    {
        foreach ($filter as &$value) {
            if (isset($value['children']) && $value['children']) {
                $value['name'] = $value['category_name'];
                $value['img'] = $value['image_url'];
                $this->processingParams($value['children']);
            } else {
                $value['name'] = $value['category_name'];
                $value['img'] = $value['image_url'];
            }
        }
        return $filter;
    }

    // 更具分类id获取所有分类名称数组
    public function getCategoryPathNameById($categoryId, $companyId, $isMainCategory = false)
    {
        $filter = ['company_id' => $companyId, 'category_id' => $categoryId, 'is_main_category' => $isMainCategory];
        $info = $this->itemsCategoryRepository->getInfo($filter);
        if (!$info) {
            return [];
        }
        $pathName = [];
        $path = explode(',', $info['path']);
        $list = $this->itemsCategoryRepository->lists(['category_id' => $path], [], -1, 1, 'category_id,category_name');
        $listMap = array_column($list['list'], null, 'category_id');
        foreach ($path as $category_id) {
            if (isset($listMap[$category_id])) {
                $pathName[] = $listMap[$category_id]['category_name'];
            }
        }

        return $pathName;
    }

    /**
     * 根据商品ID，获取到分类结构
     */
    public function getCategoryPathById($categoryId, $companyId, $isMainCategory = false)
    {
        $filter = ['company_id' => $companyId, 'category_id' => $categoryId, 'is_main_category' => $isMainCategory];

        $info = $this->itemsCategoryRepository->getInfo($filter);
        if (!$info) {
            return [];
        }

        $path = explode(',', $info['path']);
        $orderBy = ["sort" => "DESC", "created" => "ASC"];
        if (count($path) == 3) {
            $list = $this->itemsCategoryRepository->lists(['category_id' => $path], $orderBy, -1);
            $treeList = $list['list'];
        } elseif (count($path) == 2) {
            // 获取上级
            $parentData = $this->itemsCategoryRepository->lists(['category_id' => $path[0]], $orderBy, -1);
            $treeList = array_merge($parentData['list'], [$info]);

            // 获取下级
            $childrenlist = $this->itemsCategoryRepository->lists(['parent_id' => $categoryId], $orderBy, -1);
            if ($childrenlist['total_count'] > 0) {
                $treeList = array_merge($treeList, $childrenlist['list']);
            }
        } else {
            $treeList = [$info];
            // 获取所有下级
            $childrenlist = $this->itemsCategoryRepository->getChildrenByTopCatId($categoryId);
            if ($childrenlist) {
                $treeList = array_merge($treeList, $childrenlist);
            }
        }
        return $this->getTree($treeList);
    }

    /**
     * 获取指定主类目下的所有子类目id
     */
    public function getMainCatChildIdsBy($categoryId, $companyId)
    {
        $filter = ['company_id' => $companyId, 'category_id' => $categoryId, 'is_main_category' => true];

        $info = $this->itemsCategoryRepository->getInfo($filter);
        if (!$info) {
            return [];
        }
        $mainCatIds = [];
        $childrenlist['total_count'] = 0;
        $path = explode(',', $info['path']);
        if (count($path) == 3) {
            $mainCatIds = [$categoryId];
        } elseif (count($path) == 2) {
            $childrenlist = $this->itemsCategoryRepository->lists(['parent_id' => $categoryId, 'is_main_category' => true], array(), -1);
            if ($childrenlist['total_count'] > 0) {
                $mainCatIds = array_column($childrenlist['list'], 'category_id');
            }
        } else {
            $childrenlist = $this->itemsCategoryRepository->getChildrenByTopCatId($categoryId);
            if ($childrenlist) {
                $mainCatIds = array_column($childrenlist, 'category_id');
            }
        }

        return $mainCatIds ?: [-1];
    }

    /**
     * 获取单个分类信息
     * @param array $filter 过滤条件
     * @param int $itemId 商品ID，默认为0。如果不为0，则goods_spec下的attribute_values只返回该商品关联的属性值
     */
    public function getCategoryInfo($filter, $otherParams = [])
    {
        $itemInfo = $this->itemsCategoryRepository->getInfo($filter);
        if (!$itemInfo) {
            return [];
        }
        $attributeIds = array_merge($itemInfo['goods_params'], $itemInfo['goods_spec']);
        if (!$attributeIds) {
            return $itemInfo;
        }

        $itemsAttributesService = new ItemsAttributesService();
        $itemIdForAttrs = (int)($otherParams['item_id'] ?? 0);
        $attrList = $itemsAttributesService->getAttrList(['attribute_id' => $attributeIds], 1, 100, ['attribute_sort' => 'asc'], $itemIdForAttrs);

        // 如果提供了item_id，获取商品的自定义属性值
        $customAttributeValues = [];
        if (isset($otherParams['item_id']) && $otherParams['item_id']) {
            $isPoint = isset($otherParams['is_point']) ? $otherParams['is_point'] : 0;
            $customAttributeValues = $this->getItemCustomAttributeValues($otherParams['item_id'], $filter['company_id'], $isPoint, $attributeIds);
        }

        $itemInfo['goods_params'] = [];
        $itemInfo['goods_spec'] = [];
        foreach ($attrList['list'] as $row) {
            if ($row['attribute_type'] == 'item_params') {
                // 处理商品参数的自定义属性值
                // 如果有attribute_values列表，需要在对应的属性值上显示自定义值
                if (isset($row['attribute_values']['list']) && is_array($row['attribute_values']['list'])) {
                    foreach ($row['attribute_values']['list'] as &$attrValue) {
                        // 先检查是否有针对该attribute_value_id的自定义值
                        $key = $row['attribute_id'] . '_' . $attrValue['attribute_value_id'];
                        if (isset($customAttributeValues[$key])) {
                            $attrValue['custom_attribute_value'] = $customAttributeValues[$key];
                            $attrValue['attribute_value'] = $customAttributeValues[$key];
                        }
                    }
                    unset($attrValue);
                }
                // 如果attribute_id级别有自定义值，也添加到属性中
                if (isset($customAttributeValues[$row['attribute_id']])) {
                    $row['custom_attribute_value'] = $customAttributeValues[$row['attribute_id']];
                    $row['attribute_value'] = $customAttributeValues[$row['attribute_id']];
                }
                $itemInfo['goods_params'][] = $row;
            } else {
                // 处理规格属性，如果有自定义属性值，合并到属性值列表中
                if (isset($row['attribute_values']['list']) && is_array($row['attribute_values']['list'])) {
                    foreach ($row['attribute_values']['list'] as &$attrValue) {
                        $key = $row['attribute_id'] . '_' . $attrValue['attribute_value_id'];
                        if (isset($customAttributeValues[$key])) {
                            $attrValue['custom_attribute_value'] = $customAttributeValues[$key];
                            $attrValue['attribute_value'] = $customAttributeValues[$key];
                        }
                    }
                    unset($attrValue);
                }
                $itemInfo['goods_spec'][] = $row;
            }
        }

        usort($itemInfo['goods_spec'], function ($a, $b) {
            return $b['attribute_id'] <=> $a['attribute_id']; // 降序
        });

        return $itemInfo;
    }

    /**
     * 获取商品的自定义属性值
     * @param int $itemId 商品ID
     * @param int $companyId 公司ID
     * @param int $isPoint 是否积分商品，1=积分商品，0=普通商品
     * @param array $attributeIds 属性ID列表
     * @return array 自定义属性值数组，key为attribute_id或attribute_id_attribute_value_id
     */
    private function getItemCustomAttributeValues($itemId, $companyId, $isPoint, $attributeIds)
    {
        $customValues = [];
        
        // 传入的是多规格默认的default_item_id
        // 获取所有多规格的item_id
        if ($isPoint == 1) {
            // 积分商品，使用PointsmallItemRelAttributes表
            $itemRelAttributesRepository = app('registry')->getManager('default')->getRepository(PointsmallItemRelAttributes::class);
            $itemsRepository = app('registry')->getManager('default')->getRepository(PointsmallItems::class);
        } else {
            // 普通商品，使用ItemRelAttributes表
            $itemRelAttributesRepository = app('registry')->getManager('default')->getRepository(ItemRelAttributes::class);
            $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        }
        $items = $itemsRepository->list([
            'default_item_id' => $itemId,
            'company_id' => $companyId,
        ],[], 100, 1);
        $itemIds = array_column($items['list'], 'item_id');
        
        $relAttributes = $itemRelAttributesRepository->lists([
            'item_id' => $itemIds,
            'company_id' => $companyId,
            'attribute_id' => $attributeIds
        ], 1, 100);
        
        if ($relAttributes['total_count'] > 0) {
            foreach ($relAttributes['list'] as $relAttr) {
                if (!empty($relAttr['custom_attribute_value'])) {
                    if ($relAttr['attribute_type'] == 'item_params') {
                        // 商品参数类型
                        if ($relAttr['attribute_value_id']) {
                            // 如果有attribute_value_id，key为attribute_id_attribute_value_id
                            $key = $relAttr['attribute_id'] . '_' . $relAttr['attribute_value_id'];
                            $customValues[$key] = $relAttr['custom_attribute_value'];
                        } else {
                            // 如果没有attribute_value_id，key为attribute_id
                            $customValues[$relAttr['attribute_id']] = $relAttr['custom_attribute_value'];
                        }
                    } else if ($relAttr['attribute_type'] == 'item_spec' && $relAttr['attribute_value_id']) {
                        // 规格类型，key为attribute_id_attribute_value_id
                        $key = $relAttr['attribute_id'] . '_' . $relAttr['attribute_value_id'];
                        $customValues[$key] = $relAttr['custom_attribute_value'];
                    }
                }
            }
        }
        
        return $customValues;
    }

    public function getItemsCategoryIds($categoryId, $companyId)
    {
        if (is_array($categoryId)) {
            $ids = $categoryId;
        } else {
            $ids[] = $categoryId;
        }
        $itemsCategoryInfo = $this->itemsCategoryRepository->lists(['parent_id' => $categoryId, 'company_id' => $companyId]);
        if ($itemsCategoryInfo['total_count'] > 0) {
            $tmpIds = array_column($itemsCategoryInfo['list'], 'category_id');
            $ids = array_merge($ids, $tmpIds);
            $itemsCategoryInfo = $this->itemsCategoryRepository->lists(['parent_id' => $tmpIds, 'company_id' => $companyId]);
            if ($itemsCategoryInfo['total_count'] > 0) {
                $ids = array_merge($ids, array_column($itemsCategoryInfo['list'], 'category_id'));
            }
        }

        return $ids;
    }

    public function getItemIdsByCatId($categoryId, $companyId)
    {
        $catId = $this->getItemsCategoryIds($categoryId, $companyId);
        if ($catId) {
            $itemsService = new ItemsRelCatsService();
            $filter['company_id'] = $companyId;
            $filter['category_id'] = $catId;
            $data = $itemsService->lists($filter);
            if ($data['list']) {
                $itemIds = array_column($data['list'], 'item_id');
                return $itemIds;
            }
        }
        return [];
    }

    /**
     * 基于店铺可售商品清洗分类树
     * @param int $companyId
     * @param int $distributorId
     * @param array $categories
     * @return array
     */
    public function filterCategoryTreeBySaleableItems(int $companyId, int $distributorId, array $categories): array
    {
        if ($companyId <= 0 || $distributorId <= 0 || empty($categories)) {
            return $categories;
        }

        $categoryIdMap = $this->getSaleableCategoryIdMap($companyId, $distributorId);
        if (empty($categoryIdMap)) {
            return [];
        }

        return $this->filterCategoryTreeByCategoryMap($categories, $categoryIdMap);
    }

    private function getSaleableCategoryIdMap(int $companyId, int $distributorId): array
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('distinct rc.category_id, c.path')
            ->from('goods_items_rel_cats', 'rc')
            ->innerJoin('rc', 'distribution_distributor_items', 'd', 'rc.item_id = d.item_id')
            ->innerJoin('rc', 'items', 'i', 'rc.item_id = i.item_id')
            ->leftJoin('rc', 'items_category', 'c', 'rc.category_id = c.category_id AND c.company_id = :company_id')
            ->where('d.company_id = :company_id')
            ->andWhere('d.distributor_id = :distributor_id')
            ->andWhere('d.is_can_sale = 1')
            ->andWhere('d.goods_can_sale = 1')
            ->andWhere('i.approve_status IN (:approve_status)')
            ->setParameter('company_id', $companyId)
            ->setParameter('distributor_id', $distributorId)
            ->setParameter('approve_status', ['onsale', 'only_show'], Connection::PARAM_STR_ARRAY);

        $rows = $qb->execute()->fetchAll();
        $map = [];
        foreach ($rows as $row) {
            $categoryId = $row['category_id'] ?? null;
            if ($categoryId !== null && $categoryId !== '') {
                // 同时存储字符串和整数格式，确保类型匹配
                $categoryIdStr = (string)$categoryId;
                $map[$categoryIdStr] = true;
                // 如果是数字，也存储整数格式
                if (is_numeric($categoryIdStr)) {
                    $map[(int)$categoryIdStr] = true;
                }
                
                // 如果分类有路径，将路径中的所有父级分类ID也加入map
                // 这样，如果三级分类在可售列表中，它的父级分类（一级和二级）也会在map中
                $path = $row['path'] ?? '';
                if ($path) {
                    $pathIds = explode(',', $path);
                    foreach ($pathIds as $pathId) {
                        $pathId = trim($pathId);
                        if ($pathId !== '' && $pathId !== $categoryIdStr) {
                            $map[(string)$pathId] = true;
                            if (is_numeric($pathId)) {
                                $map[(int)$pathId] = true;
                            }
                        }
                    }
                }
            }
        }
        return $map;
    }

    private function filterCategoryTreeByCategoryMap(array $categories, array $categoryIdMap): array
    {
        $result = [];
        foreach ($categories as $category) {
            // 保存原始是否有子节点的状态
            $hasOriginalChildren = isset($category['children']) && !empty($category['children']);
            
            // 先递归处理子节点
            if ($hasOriginalChildren) {
                $category['children'] = $this->filterCategoryTreeByCategoryMap($category['children'], $categoryIdMap);
            }

            // 确保 category_id 转换为字符串，兼容整数和字符串类型
            $categoryId = isset($category['category_id']) ? (string)$category['category_id'] : '';
            
            // 判断是否有保留的子节点（过滤后）
            $hasFilteredChildren = !empty($category['children']);
            
            // 判断当前节点是否在可售列表中（同时检查字符串和整数格式）
            $isInSaleableMap = false;
            if ($categoryId !== '') {
                // 检查字符串格式
                if (isset($categoryIdMap[$categoryId])) {
                    $isInSaleableMap = true;
                } elseif (is_numeric($categoryId)) {
                    // 如果字符串格式不匹配，尝试整数格式
                    $categoryIdInt = (int)$categoryId;
                    if (isset($categoryIdMap[$categoryIdInt])) {
                        $isInSaleableMap = true;
                    }
                }
            }
            
            // 保留条件：当前节点在可售列表中，或者有保留的子节点（说明子节点中有可售的）
            if ($hasFilteredChildren || $isInSaleableMap) {
                $result[] = $category;
            }
        }
        return $result;
    }

    /**
     * 添加分类
     *
     * @param array params 分类数据
     * @return array
     */
    public function saveItemsCategory($data, $companyId, $distributorId)
    {
        $data['form'] = $data;
        $data['company_id'] = $companyId;
        $data['distributor_id'] = $distributorId;
        return $result = BusService::instance('goods')->post('/service/goods/category', $data);
    }

    /**
     * 添加分类
     */
    public function createClassificationService($params, $companyId, $distributorId, $level = 1, $parentId = 0, $path = "", $is_main_category = 0)
    {
        $params['category_level'] = $level;
        $params['company_id'] = $companyId;
        $params['distributor_id'] = $distributorId;
        $params['path'] = $path;
        if (!isset($params['is_main_category'])) {
            $params['is_main_category'] = $is_main_category;
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if (!isset($params['parent_id'])) {
                $params['parent_id'] = $parentId;
                $uniqueName = $this->itemsCategoryRepository->getInfo(['category_name' => $params['category_name'],'parent_id' => $params['parent_id'],'company_id' => $companyId,'is_main_category' => $params['is_main_category'],'distributor_id' => $distributorId]);
                if ($uniqueName) {
                    throw new ResourceException(trans('GoodsBundle/Controllers/Items.category_name_exists'));
                } else {
                    $res = $this->itemsCategoryRepository->create($params);
                    $updPath = $this->itemsCategoryRepository->updateOneBy(['category_id' => $res['category_id']], ['path' => $res['category_id']]);
                    if ($res && $updPath) {
                        $result = ['status' => true];
                    }
                }
            } else {
                $uniqueName = $this->itemsCategoryRepository->getInfo(['category_name' => $params['category_name'],'parent_id' => $params['parent_id'],'company_id' => $companyId,'is_main_category' => $params['is_main_category'],'distributor_id' => $distributorId]);
                if ($uniqueName) {
                    throw new ResourceException(trans('GoodsBundle/Controllers/Items.category_name_exists'));
                }

                $parentInfo = $this->itemsCategoryRepository->getInfo(['category_id' => $params['parent_id']]);
                if ($parentInfo['parent_id'] == 0) {
                    $params['category_level'] = $level + 1;
                    $path = $parentInfo['path'];
                } else {
                    $params['category_level'] = $parentInfo['category_level'] + 1;
                    $path = $parentInfo['path'];
                }
                $res = $this->itemsCategoryRepository->create($params);
                $updPath = $this->itemsCategoryRepository->updateOneBy(['category_id' => $res['category_id']], ['path' => $path.','.$res['category_id']]);
                if ($res && $updPath) {
                    $result = ['status' => true];
                }
            }

            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage().'->'.$e->getFile().'@'.$e->getLine());
        }
    }

    /**
     * 保存分类
     */
    private function __saveCategory($data, $companyId, $distributorId, $level = 1, $parentId = 0, $path = "")
    {
        foreach ($data as $row) {
            $params = [
                'category_name' => $row['category_name'],
                'company_id' => $companyId,
                'parent_id' => $parentId,
                'is_main_category' => $row['is_main_category'] ?? false,
                'category_level' => $level,
                'path' => $path,
                'sort' => $row['sort'],
                'goods_params' => $row['goods_params'] ?? null,
                'goods_spec' => $row['goods_spec'] ?? null,
                'image_url' => $row['image_url'],
                'distributor_id' => $distributorId,
            ];
            if (isset($row['category_id'])) {
                $result = $this->itemsCategoryRepository->updateOneBy(['category_id' => $row['category_id'], 'company_id' => $companyId], $params);
            } else {
                $result = $this->itemsCategoryRepository->create($params);
            }

            if ($result['path']) {
                $newPath = $result['path']. ',' . $result['category_id'];
            } else {
                $newPath = $result['category_id'];
            }
            $result = $this->itemsCategoryRepository->updateOneBy(['category_id' => $result['category_id']], ['path' => $newPath]);

            if (isset($row['children']) && $row['children']) {
                $this->__saveCategory($row['children'], $companyId, $distributorId, $level + 1, $result['category_id'], $result['path']);
            }
        }

        return true;
    }

    /**
     * 删除分类
     *
     * @param array filter
     * @return bool
     */
    public function deleteItemsCategory($filter)
    {
        return $result = BusService::instance('goods')->delete('/service/goods/category/'.$filter['category_id'], $filter);
    }

    /**
     * 递归实现无限极分类
     * @param $array 分类数据
     * @param $pid 父ID
     * @param $level 分类级别
     * @return $list 分好类的数组 直接遍历即可 $level可以用来遍历缩进
     */

    public function getTree($array, $pid = 0, $level = 0, $isShowChildren = true)
    {
        $list = [];
        foreach ($array as $k => $v) {
            $v['children'] = [];
            if ($v['parent_id'] == $pid) {
                $v['level'] = $level;
                $v['children'] = $this->getTree($array, $v['category_id'], $level + 1, $isShowChildren);
                if ($v['category_level'] == 3) {
                    unset($v['children']);
                }
                if (!$isShowChildren && false == $v['is_main_category'] && isset($v['children']) && empty($v['children'])) {
                    unset($v['children']);
                }
                $list[] = $v;
            }
        }
        return $list;
    }
    /**
     * 更新数据表字段数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data = [])
    {
        return BusService::instance('goods')->put("/service/goods/category/{$filter['company_id']}/{$filter['category_id']}", $data);
    }

    /**
     * 格式化商品分类数据
     * @param  array  $category 商品分类数据
     * @return array           处理后的商品分类数据
     * @author Toby.Tu 2024-04-18
     */
    public function formateCategoryInfo(array $category=[]) : array {
        $category_level = 0;
        if ( $category['category_level'] ?? '' ) {
            $category_level = $category['category_level'] ?? 0;
        } else if ( $category['level'] ?? '' ) {
            $category_level = $category['level'] ?? 0;
        }
        $_category = [
            'category_id' => $category['category_id'],
            'category_name' => $category['category_name'],
            'category_level' => $category_level,
            'parent_id' => $category['parent_id'],
            'path' => $category['path'],
            'sort' => $category['sort'],
            'image_url' => $category['image_url'],
            'distributor_id' => $category['distributor_id'],
            'customize_page_id' => $category['customize_page_id'] ?? 0,
        ];
        if (isset($category['children']) && $category['children']) {
            foreach ($category['children'] as $key => $children) {
                $_category['children'][$key] = $this->formateCategoryInfo($children);
            }
        }
        return $_category;
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->itemsCategoryRepository->$method(...$parameters);
    }

    /**
     * 判断items_category表里面category_ids是否已有不同的invoice_tax_rate_id
     * @param int $invoiceTaxRateId
     * @return array
     */
    public function getItemsCategoryByInvoiceTaxRateId($categoryIds,$invoiceTaxRateId = 0 )
    {
        $filter = [ 'invoice_tax_rate_id|gt' => 0, 'category_id' => $categoryIds];
        if($invoiceTaxRateId >  0){
            $filter['invoice_tax_rate_id|neq'] = $invoiceTaxRateId;
        }
        app('log')->info(__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));
        $list = $this->itemsCategoryRepository->lists($filter, [], -1, 1, 'category_id,category_name');

        app('log')->info(__FUNCTION__.':'.__LINE__.':list:'.json_encode($list));
        $listMap = array_column($list['list'], null, 'category_id');
        app('log')->info(__FUNCTION__.':'.__LINE__.':listMap:'.json_encode($listMap));
        return $listMap;
    }

    /**
     * 批量更新指定发票税率ID的分类
     * @param int $invoiceTaxRateId
     * @param array $data
     * @return int 受影响行数
     */
    public function updateInvoiceTaxRateId($id, array $data)
    {
        app('log')->info(__FUNCTION__.':'.__LINE__.':category:id:'.json_encode($id));
        $filter = ['category_id' => $id];
        app('log')->info(__FUNCTION__.':'.__LINE__.':category:filter:'.json_encode($filter));
        app('log')->info(__FUNCTION__.':'.__LINE__.':category:data:'.json_encode($data));
        $res = $this->itemsCategoryRepository->updateBy($filter, $data);
        app('log')->info(__FUNCTION__.':'.__LINE__.':category:DONE:'.json_encode($res));
        return $res;
    }


}
