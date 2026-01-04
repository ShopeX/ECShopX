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

namespace CompanysBundle\Services;

use CompanysBundle\Entities\ArticleCategory;
use Dingo\Api\Exception\ResourceException;

class ArticleCategoryService
{
    /**
     * @var articleCategoryRepository
     */
    private $articleCategoryRepository;

    /**
     * ItemsService 构造函数.
     */
    public function __construct()
    {
        // $this->articleCategoryRepository = app('registry')->getManager('default')->getRepository(ArticleCategory::class);
        $this->articleCategoryRepository = getRepositoryLangue(ArticleCategory::class);
    }

    /**
     * 获取所有分类
     */
    public function getArticleCategory($filter, $page = 1, $pageSize = 1000, $orderBy = ["sort" => "DESC", "created" => "ASC"])
    {
        if (!isset($filter['category_type'])) {
            $filter['category_type'] = 'bring';
        }
        $cols = 'category_id,company_id,category_name,parent_id,category_level,path,sort,category_type';
        $articleCategoryInfo = $this->articleCategoryRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        return $this->getTree($articleCategoryInfo['list']);
    }

    /**
     * 根据商品ID，获取到分类结构
     */
    public function getCategoryPathById($categoryId, $companyId)
    {
        $filter = ['company_id' => $companyId, 'category_id' => $categoryId];

        $info = $this->articleCategoryRepository->getInfo($filter);
        if (!$info) {
            return [];
        }

        $path = explode(',', $info['path']);
        $orderBy = ["sort" => "DESC", "created" => "ASC"];
        if (count($path) == 3) {
            $list = $this->articleCategoryRepository->lists(['category_id' => $path], '*', 1, -1, $orderBy);
            $treeList = $list['list'];
        } elseif (count($path) == 2) {
            // 获取上级
            $parentData = $this->articleCategoryRepository->lists(['category_id' => $path[0]], '*', 1, -1, $orderBy);
            $treeList = array_merge($parentData['list'], [$info]);

            // 获取下级
            $childrenlist = $this->articleCategoryRepository->lists(['parent_id' => $categoryId], '*', 1, -1, $orderBy);
            if ($childrenlist['total_count'] > 0) {
                $treeList = array_merge($treeList, $childrenlist['list']);
            }
        } else {
            $treeList = [$info];
            // 获取所有下级
            $childrenlist = $this->articleCategoryRepository->getChildrenByTopCatId($categoryId);
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
        $filter = ['company_id' => $companyId, 'category_id' => $categoryId];

        $info = $this->articleCategoryRepository->getInfo($filter);
        if (!$info) {
            return [];
        }
        $mainCatIds = [];
        $childrenlist['total_count'] = 0;
        $path = explode(',', $info['path']);
        if (count($path) == 2) {
            $childrenlist = $this->articleCategoryRepository->lists(['parent_id' => $categoryId], '*', 1, -1, array());
            if ($childrenlist['total_count'] > 0) {
                $mainCatIds = array_column($childrenlist['list'], 'category_id');
            }
        } else {
            $childrenlist = $this->articleCategoryRepository->getChildrenByTopCatId($categoryId);
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
        $itemInfo = $this->articleCategoryRepository->getInfo($filter);
        if (!$itemInfo) {
            return [];
        }
        return $itemInfo;
    }

    public function getArticleCategoryIds($categoryId, $companyId)
    {
        if (is_array($categoryId)) {
            $ids = $categoryId;
        } else {
            $ids[] = $categoryId;
        }
        $articleCategoryInfo = $this->articleCategoryRepository->lists(['parent_id' => $categoryId, 'company_id' => $companyId]);
        foreach ($articleCategoryInfo['list'] as $v) {
            $ids[] = $v['category_id'];
        }

        return $ids;
    }

    /**
     * 添加分类
     *
     * @param array params 分类数据
     * @return array
     */
    public function saveArticleCategory($data, $companyId)
    {
        $info = json_decode($data, true);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->__saveCategory($info, $companyId);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        } catch (\Throwable $e) {
            $conn->rollback();
            throw new ResourceException('保存失败');
        }

        return ['status' => true];
    }

    /**
     * 保存分类
     */
    private function __saveCategory($data, $companyId, $level = 1, $parentId = 0, $path = "")
    {
        foreach ($data as $row) {
            $params = [
                'category_name' => $row['category_name'],
                'company_id' => $companyId,
                'parent_id' => $parentId,
                'category_level' => $level,
                'path' => $path,
                'sort' => $row['sort'] ? $row['sort'] : 0,
                'category_type' => $row['category_type'] ?? 'bring',
            ];

            if (!is_numeric($params['sort'])) {
                throw new ResourceException('排序必须为数字类型');
            }
            if (isset($row['category_id'])) {
                $result = $this->articleCategoryRepository->updateOneBy(['category_id' => $row['category_id'], 'company_id' => $companyId], $params);
            } else {
                $result = $this->articleCategoryRepository->create($params);
            }

            if ($result['path']) {
                $newPath = $result['path']. ',' . $result['category_id'];
            } else {
                $newPath = $result['category_id'];
            }
            $result = $this->articleCategoryRepository->updateOneBy(['category_id' => $result['category_id']], ['path' => $newPath]);

            if (isset($row['children']) && $row['children']) {
                $this->__saveCategory($row['children'], $companyId, $level + 1, $result['category_id'], $result['path']);
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
    public function deleteArticleCategory($filter)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $mainCatIds = $this->getMainCatChildIdsBy($filter['category_id'], $filter['company_id']);
            $mainCatIds[] = $filter['category_id'];
            // 判断是否为主类目
            $result = $this->articleCategoryRepository->deleteBy(['category_id' => $filter['category_id'], 'company_id' => $filter['company_id']]);
            $resultAll = true;
            $articleCategoryInfo = $this->articleCategoryRepository->getInfo(['parent_id' => $filter['category_id']]);

            if ($articleCategoryInfo) {
                $resultAll = $this->articleCategoryRepository->deleteBy(['parent_id' => $filter['category_id'], 'company_id' => $filter['company_id']]);
            }
            if ($result && $resultAll) {
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
     * @param $array 分类数据
     * @param $pid 父ID
     * @param $level 分类级别
     * @return $list 分好类的数组 直接遍历即可 $level可以用来遍历缩进
     */

    public function getTree($array, $pid = 0, $level = 0)
    {
        $list = [];
        foreach ($array as $k => $v) {
            if ($v['parent_id'] == $pid) {
                $v['level'] = $level;
                $v['children'] = $this->getTree($array, $v['category_id'], $level + 1);
                if (!$v['children'] && $v['parent_id'] != 0) {
                    unset($v['children']);
                }
                $list[] = $v;
            }
        }
        return $list;
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
        return $this->articleCategoryRepository->$method(...$parameters);
    }
}
