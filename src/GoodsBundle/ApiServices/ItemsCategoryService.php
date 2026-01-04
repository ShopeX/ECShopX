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

use GoodsBundle\Entities\ItemsCategory;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Events\ItemCategoryAddEvent;
use GoodsBundle\Services\ItemsRelCatsService;

class ItemsCategoryService
{
    /**
     * @var itemsCategoryRepository
     */
    private $itemsCategoryRepository;

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
    public function getItemsCategory($filter, $isShow = true, $page = 1, $pageSize = 1000, $orderBy = ["sort" => "DESC", "created" => "ASC"], $columns = "*")
    {
        if (isset($filter['parent_id']) || isset($filter['category_level'])) {
            $itemsCategoryInfo = $this->itemsCategoryRepository->getSingleLevelList($filter, $orderBy, $pageSize, $page, $columns);
        } else {
            $itemsCategoryInfo = $this->itemsCategoryRepository->lists($filter, $orderBy, $pageSize, $page, $columns);
        }
        $pid = $filter['parent_id'] ?? 0;
        $level = isset($filter['category_level']) ? $filter['category_level'] - 1 : 0;
        return $this->getTree($itemsCategoryInfo['list'], $pid, $level, $isShow);
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
        if (count($path) == 2) {
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

        return $mainCatIds;
    }

    /**
     * 获取单个分类信息
     */
    public function getCategoryInfo($filter)
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
        $attrList = $itemsAttributesService->getAttrList(['attribute_id' => $attributeIds], 1, 100);
        $itemInfo['goods_params'] = [];
        $itemInfo['goods_spec'] = [];
        foreach ($attrList['list'] as $row) {
            if ($row['attribute_type'] == 'item_params') {
                $itemInfo['goods_params'][] = $row;
            } else {
                $itemInfo['goods_spec'][] = $row;
            }
        }
        usort($itemInfo['goods_spec'], function ($a, $b) {
            return $b['attribute_id'] <=> $a['attribute_id'];
        });

        return $itemInfo;
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
     * 添加分类
     *
     * @param array params 分类数据
     * @return array
     */
    public function saveItemsCategory($data, $companyId, $distributorId)
    {
//        $info = json_decode($data, true);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->checkCategoryData($data);

            $this->__saveCategory($data, $companyId, $distributorId);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        } catch (\Throwable $e) {
            $conn->rollback();
            throw new ResourceException('保存失败');
        }

        //触发事件监听
        app('log')->debug('触发事件监听');
        $eventData = [
            'company_id' => $companyId
        ];
        event(new ItemCategoryAddEvent($eventData));

        return ['status' => true];
    }

    /**
     * 校验分类数据
     *
     * @param array params 分类数据
     * @return array
     */
    public function checkCategoryData($data)
    {
        foreach ($data as $key => $value) {
            if (empty($value['category_name'])) {
                throw new ResourceException('分类名称必填');
            }
            if (strlen($value['category_name']) > 50) {
                throw new ResourceException('分类名称长度最多16个汉字或50个字符');
            }
            if (!empty($value['children'])) {
                $this->checkCategoryData($value['children']);
            }
        }
        return true;
    }

    /**
     * 保存分类
     */
    private function __saveCategory($data, $companyId, $distributorId, $level = 1, $parentId = 0, $path = "")
    {
        foreach ($data as $row) {
            //category_code 字段 用来兼容OME分类
            if (isset($row['category_code']) && $row['category_code']) {
                $catInfo = $this->itemsCategoryRepository->getInfo(['category_code' => $row['category_code']]);
                if ($catInfo) {
                    $row['category_id'] = $catInfo['category_id'];
                }
            }
            $params = [
                'category_name' => $row['category_name'],
                'company_id' => $companyId,
                'parent_id' => $parentId,
                'is_main_category' => $row['is_main_category'] ?? false,
                'category_level' => $level,
                'path' => $path,
                'category_code' => isset($row['category_code']) ? $row['category_code'] : '',
                'sort' => $row['sort'],
                'goods_params' => $row['goods_params'] ?? [],
                'goods_spec' => $row['goods_spec'] ?? [],
                'image_url' => $row['image_url'],
                'distributor_id' => $distributorId,
                'category_id_taobao' => $row['category_id_taobao'] ?? 0,
                'parent_id_taobao' => $row['parent_id_taobao'] ?? 0,
                'taobao_category_info' => json_encode($row['taobao_category_info'] ?? []),
            ];
            if (isset($row['category_id']) && $row['category_id']) {
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
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $ids = $this->getItemIdsByCatId($filter['category_id'], $filter['company_id']);
            $itemsService = new ItemsService();
            if ($ids) {
                $itemsInfo = $itemsService->getItemsList(['company_id' => $filter['company_id'], 'item_id|in' => $ids], 1, 1);
                if ($itemsInfo['total_count'] > 0) {
                    throw new ResourceException('分类下存在商品');
                }
            }

            $mainCatIds = $this->getMainCatChildIdsBy($filter['category_id'], $filter['company_id']);
            $mainCatIds[] = $filter['category_id'];
            // 判断是否为主类目
            $itemTotalCount = $itemsService->count(['company_id' => $filter['company_id'], 'item_category' => $mainCatIds]);
            if ($itemTotalCount > 0) {
                throw new ResourceException('类目下存在商品');
            }

            if ($ids) {
                $itemsService = new ItemsRelCatsService();
                $itemsService->deleteBy(['category_id' => $filter['category_id'], 'item_id' => $ids, 'company_id' => $filter['company_id'] ]);
            }
            $result = $this->itemsCategoryRepository->deleteBy(['category_id' => $filter['category_id'], 'company_id' => $filter['company_id']]);
            $resultChild = true;
            $resultChildSun = true;
            $itemsCategoryInfo = $this->itemsCategoryRepository->getInfo(['parent_id' => $filter['category_id']]);

            if ($itemsCategoryInfo) {
                $resultChildList = $this->itemsCategoryRepository->lists(['parent_id' => $filter['category_id'], 'company_id' => $filter['company_id']]);
                $resultChild = $this->itemsCategoryRepository->deleteBy(['parent_id' => $filter['category_id'], 'company_id' => $filter['company_id']]);
                if ($resultChildList['total_count'] > 0) {
                    foreach ($resultChildList['list'] as $v) {
                        $resultChildSunList = $this->itemsCategoryRepository->lists(['parent_id' => $v['category_id'], 'company_id' => $v['company_id']]);
                        if ($resultChildSunList['total_count'] > 0) {
                            $resultChildSun = $this->itemsCategoryRepository->deleteBy(['parent_id' => $v['category_id'], 'company_id' => $v['company_id']]);
                        }
                    }
                }
            }
            if ($result && $resultChild && $resultChildSun) {
                $conn->commit();
                return true;
            } else {
                throw new ResourceException('删除失败');
            }
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 递归实现无限极分类
     * @param array $array 分类数据
     * @param int $pid 父ID
     * @param int $level 分类级别
     * @param bool $isShowChildren 是否显示子分类
     * @return array 分好类的数组
     */
    public function getTree(array $array, int $pid = 0, int $level = 0, bool $isShowChildren = true): array
    {
        // 构建索引数组以提高查找效率
        $indexedArray = [];
        foreach ($array as $item) {
            $indexedArray[$item['parent_id']][] = $item;
        }
        
        // 递归构建树结构
        return $this->buildTree($indexedArray, $pid, $level, $isShowChildren);
    }

    /**
     * 构建树结构
     * @param array $indexedArray 索引后的分类数据
     * @param int $pid 父ID
     * @param int $level 当前层级
     * @param bool $isShowChildren 是否显示子分类
     * @return array
     */
    private function buildTree(array &$indexedArray, int $pid, int $level, bool $isShowChildren): array 
    {
        $tree = [];
        
        if (!isset($indexedArray[$pid])) {
            return $tree;
        }

        foreach ($indexedArray[$pid] as $item) {
            $node = $item;
            $node['level'] = $level;
            // 默认设置空的children数组，保持与旧版本一致
            $node['children'] = [];
            
            // 处理子节点
            if (isset($indexedArray[$item['category_id']])) {
                $node['children'] = $this->buildTree($indexedArray, $item['category_id'], $level + 1, $isShowChildren);
            }

            // 与旧版本保持一致的条件判断
            if ($node['category_level'] == 3) {
                unset($node['children']);
            } elseif (!$isShowChildren && false == ($node['is_main_category'] ?? false) && isset($node['children']) && empty($node['children'])) {
                unset($node['children']);
            }
            
            $tree[] = $node;
        }
        
        return $tree;
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data = [])
    {
        return $this->itemsCategoryRepository->updateOneBy($filter, $data);
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
}
