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

namespace GoodsBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use GoodsBundle\Entities\ItemsCategory;
use CompanysBundle\Ego\CompanysActivationEgo;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangService;
use CompanysBundle\MultiLang\MultiLangItem;
use Illuminate\Http\Request;

class ItemsCategoryRepository extends EntityRepository
{
    use MagicLangTrait;
    public $table = 'items_category';
    private $prk = 'category_id';

    private $multiLangField = [
        'category_name',
    ];

    public function getDistributorId($data)
    {
        // Powered by ShopEx EcShopX
        if (!isset($data['category_id']) && !isset($data['parent_id'])) {
            if (isset($data['is_main_category']) && $data['is_main_category']) {
                $data['distributor_id'] = 0;
            } else {
                $distributorId = 0;
                $companyId = app('auth')->user() ? app('auth')->user()->get('company_id') : ($data['company_id'] ?? 0);
                $company = (new CompanysActivationEgo())->check($companyId);
                if ($company['product_model'] == 'platform') {
                    if (app('auth')->user() && app('auth')->user()->get('distributor_id')) {
                        $distributorId = app('auth')->user()->get('distributor_id');
                    }
                }
                $data['distributor_id'] = $distributorId ?: $data['distributor_id'] ?? 0;
            }
        }
        return $data;
    }


    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {

        $service = new MultiLangService();
//        $dataTmp = $service->getLangData($data,$this->multiLangField);
//        $data = $dataTmp['data'];
//        $langBag = $dataTmp['langBag'];
        $data = $this->getDistributorId($data);

        $entity = new ItemsCategory();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $dataRet = $this->getColumnNamesData($entity);
        $service->addMultiLangByParams($dataRet[$this->prk],$data,$this->table);
//        $service->saveLang($dataRet['company_id'],$langBag,$this->table,$dataRet['category_id'],$this->table);
        return $dataRet;
    }

    /**
     * 获取一级分类的所有子分类
     */
    public function getChildrenByTopCatId($categoryId)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select('*')->from($this->table);

        $qb = $qb->andWhere($qb->expr()->like('path', $qb->expr()->literal($categoryId.',%')));

        return $qb->execute()->fetchAll();
    }

    public function getTopByChildrenId($categoryId)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select('category_id,category_name,is_main_category,parent_id,sort')->from($this->table);
        if (is_array($categoryId)) {
            $qb->andWhere($qb->expr()->in('category_id', $categoryId));
        } else {
            $qb->andWhere($qb->expr()->eq('category_id', $categoryId));
        }
        $lv3List = $qb->execute()->fetchAll();

        $result = [];
        $lv2CategoryIds = [];
        $lv1CategoryIds = [];
        foreach ($lv3List as $item) {
            if ($item['parent_id'] == 0) {
                $result[] = $item;
            } else {
                $lv2CategoryIds[] = $item['parent_id'];
            }
        }
        if ($lv2CategoryIds) {
            $qb = $conn->createQueryBuilder()->select('category_id,category_name,is_main_category,parent_id,sort')->from($this->table);
            $qb->andWhere($qb->expr()->in('category_id', $lv2CategoryIds));
            $lv2List = $qb->execute()->fetchAll();
            foreach ($lv2List as $item) {
                if ($item['parent_id'] == 0) {
                    $result[] = $item;
                } else {
                    $lv1CategoryIds[] = $item['parent_id'];
                }
            }
        }

        if ($lv1CategoryIds) {
            $qb = $conn->createQueryBuilder()->select('category_id,category_name,is_main_category,parent_id,sort')->from($this->table);
            $qb->andWhere($qb->expr()->in('category_id', $lv1CategoryIds));
            $lv1List = $qb->execute()->fetchAll();
            foreach ($lv1List as $item) {
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data)
    {
        $filter = $this->getDistributorId($filter);

        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException("未查询到更新数据");
        }


        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        if(isset($filter[$this->prk])){
            $service = new MultiLangService();
            $service->updateLangData($data,'items_category',$filter[$this->prk]);
        }

        return $this->getColumnNamesData($entity);
    }

    /**
     * 更新多条数数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateBy(array $filter, array $data)
    {
        $filter = $this->getDistributorId($filter);

        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new ResourceException("未查询到更新数据");
        }

        $em = $this->getEntityManager();
        $result = [];
        foreach ($entityList as $entityProp) {
            $entityProp = $this->setColumnNamesData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $result[] = $this->getColumnNamesData($entityProp);
        }
        return $result;
    }

    /**
     * 根据主键删除指定数据
     *
     * @param $id
     */
    public function deleteById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            throw new \Exception("删除的数据不存在");
        }
        $em = $this->getEntityManager();
        $em->remove($entity);
        $em->flush();
        return true;
    }

    /**
     * 根据条件删除指定数据
     *
     * @param $filter 删除的条件
     */
    public function deleteBy($filter)
    {
        $filter = $this->getDistributorId($filter);

        $entityList = $this->findBy($filter);
        if (!$entityList) {
            return true;
        }
        $em = $this->getEntityManager();
        foreach ($entityList as $entityProp) {
            $em->remove($entityProp);
            $em->flush();
        }
        return true;
    }

    /**
     * 根据主键获取数据
     *
     * @param $id
     */
    public function getInfoById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            return [];
        }

        $data =  $this->getColumnNamesData($entity);
        $service = new MultiLangService();
        $lang = $this->getLang();
        $data = $service->getOneLangData($data,[],$this->table,$lang,$data[$this->prk],$this->table);
        return $data;
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function getInfo(array $filter)
    {
        $filter = $this->getDistributorId($filter);

        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }

    /**
     * 统计数量
     */
    public function count($filter)
    {
        $filter = $this->getDistributorId($filter);

        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }

        $total = $this->getEntityManager()
                      ->getUnitOfWork()
                      ->getEntityPersister($this->getEntityName())
                      ->count($criteria);

        return intval($total);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1, $columns = '*')
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
            ->from('items_category');
        $filter = $this->getDistributorId($filter);
        $criteria = $this->_filter($filter, $criteria);

        $result['total_count'] = $criteria->execute()->fetchColumn();
        $result['list'] = [];
        if ($result['total_count'] > 0) {
            if ($pageSize > 0) {
                $criteria->setFirstResult(($page - 1) * $pageSize)
                    ->setMaxResults($pageSize);
            }
            if ($orderBy) {
                foreach ($orderBy as $filed => $val) {
                    $criteria->addOrderBy($filed, $val);
                }
            }
            $result['list'] = $criteria->select($columns)->execute()->fetchAll();
            app('log')->debug("items_category lists sql =>:".$criteria->getSQL());
        }


        //获取语言
        $lang = $this->getLang();
        $result['list'] = (new MultiLangService())->getListAddLang($result['list'],$this->multiLangField,$this->table,$lang,'category_id');
        return $result;
    }

    /**
     * 根据多语言分类名称查找分类列表
     * 先通过多语言表查找 category_id，再查询主表信息
     *
     * @param array $filter 查询条件，如果包含 category_name，会通过多语言表查找
     * @param array $orderBy 排序
     * @param int $pageSize 每页数量
     * @param int $page 页码
     * @param string $columns 查询字段
     * @return array
     */
    public function listsByCategoryName($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1, $columns = '*')
    {
        // 如果 filter 中包含 category_name，先通过多语言表查找对应的 category_id
        if (isset($filter['category_name']) && !empty($filter['category_name'])) {
            $categoryNames = is_array($filter['category_name']) ? $filter['category_name'] : [$filter['category_name']];
            $categoryIds = $this->getCategoryIdsByNames($categoryNames, $filter);
            
            // 如果通过多语言表找到了 category_id，替换 filter 中的 category_name
            if (!empty($categoryIds)) {
                // 移除 category_name，添加 category_id
                unset($filter['category_name']);
                // 如果 filter 中已有 category_id，需要合并
                if (isset($filter['category_id'])) {
                    $existingIds = is_array($filter['category_id']) ? $filter['category_id'] : [$filter['category_id']];
                    $categoryIds = array_unique(array_merge($existingIds, $categoryIds));
                }
                $filter['category_id'] = $categoryIds;
            }
            // 如果多语言表中没找到，保留原 filter，让 lists 方法在主表中查找默认语言的值
        }

        // 调用原有的 lists 方法
        return $this->lists($filter, $orderBy, $pageSize, $page, $columns);
    }

    /**
     * 根据分类名称（支持多语言）查找对应的 category_id
     *
     * @param array $categoryNames 分类名称数组
     * @param array $filter 额外的过滤条件（如 company_id, distributor_id, is_main_category 等）
     * @return array category_id 数组
     */
    private function getCategoryIdsByNames(array $categoryNames, array $filter = [])
    {
        if (empty($categoryNames)) {
            return [];
        }

        $lang = $this->getLang();
        $multiLangItem = new MultiLangItem($lang);
        
        // 从多语言表中查找（只根据名称查找，其他条件在主表中验证）
        $langFilter = [
            'table_name' => $this->table,
            'field' => 'category_name',
            'attribute_value|in' => $categoryNames
        ];

        $langList = $multiLangItem->getListByFilter($langFilter, -1);
        
        if (empty($langList)) {
            return [];
        }

        $categoryIds = array_column($langList, 'data_id');
        $categoryIds = array_unique($categoryIds);

        // 验证这些 category_id 是否满足 filter 中的其他条件（如 company_id, distributor_id, is_main_category）
        if (!empty($categoryIds)) {
            $verifyFilter = ['category_id' => $categoryIds];
            
            // 合并其他过滤条件
            foreach (['company_id', 'distributor_id', 'is_main_category'] as $key) {
                if (isset($filter[$key])) {
                    $verifyFilter[$key] = $filter[$key];
                }
            }
            
            // 如果没有任何额外条件，直接返回
            if (count($verifyFilter) === 1) {
                return $categoryIds;
            }
            
            // 在主表中验证这些 ID 是否满足条件
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            $qb->select('category_id')
                ->from($this->table);
            $qb = $this->_filter($verifyFilter, $qb);
            $verifiedIds = $qb->execute()->fetchAll(\PDO::FETCH_COLUMN);
            
            return $verifiedIds;
        }

        return [];
    }

    public function getSingleLevelList($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1, $columns = "*") {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('count(*)')
            ->from('items_category', 'p');

        $filter = $this->getDistributorId($filter);
        $criteria = $this->_filter($filter, $criteria, 'p');

        $result['total_count'] = $criteria->execute()->fetchColumn();
        $result['list'] = [];
        if ($result['total_count'] > 0) {
            $criteria->leftJoin('p', 'items_category', 'c', 'p.category_id = c.parent_id')
                ->groupBy('p.category_id');
            if ($pageSize > 0) {
                $criteria->setFirstResult(($page - 1) * $pageSize)
                    ->setMaxResults($pageSize);
            }
            if ($orderBy) {
                foreach ($orderBy as $filed => $val) {
                    $criteria->addOrderBy('p.'.$filed, $val);
                }
            }
            if (!$columns || $columns == '*') {
                $columns = 'p.*';
            } else {
                $columns = explode(',', $columns);
                $columns = array_map(function ($val) {
                    return 'p.'.$val;
                }, $columns);
                $columns = implode(',', $columns);
            }
            $result['list'] = $criteria->select($columns.',(CASE WHEN c.category_id IS NULL THEN 0 ELSE 1 END) as has_children')->execute()->fetchAll();
        }
//获取语言
        $lang = $this->getLang();
        $result['list'] = (new MultiLangService())->getListAddLang($result['list'],$this->multiLangField,$this->table,$lang,$this->prk);


        return $result;
    }

    private function _filter($filter, $qb, $alias = '')
    {
        foreach ($filter as $field => $value) {
            if (is_bool($value)) {
                $value = $value ? 1 : 0;
            }
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'direct') {
                    $qb = $qb->andWhere($qb->expr()->eq(($alias ? $alias.'.' : '').$v, $value));
                    continue;
                }
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
                    $value = '%'.$value.'%';
                }
                if ($k == 'startWith') {
                    $k = 'like';
                    $value = $value.'%';
                }
                if ($k == 'startsWith') {
                    $k = 'like';
                    $value = $value.'%';
                }
                if (is_array($value)) {
                    if (!$value) continue;
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k(($alias ? $alias.'.' : '').$v, $value));
                } else {
                    if (is_string($value)) {
                        $qb = $qb->andWhere($qb->expr()->$k(($alias ? $alias.'.' : '').$v, $qb->expr()->literal($value)));
                    } else {
                        $qb = $qb->andWhere($qb->expr()->$k(($alias ? $alias.'.' : '').$v, $value));
                    }
                }
            } else {
                if (is_array($value)) {
                    if (!$value) continue;
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->in(($alias ? $alias.'.' : '').$field, $value));
                } else {
                    if (is_string($value)) {
                        $qb = $qb->andWhere($qb->expr()->eq(($alias ? $alias.'.' : '').$field, $qb->expr()->literal($value)));
                    } else {
                        $qb = $qb->andWhere($qb->expr()->eq(($alias ? $alias.'.' : '').$field, $value));
                    }
                }
            }
        }
        return $qb;
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function listsCopy($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res["total_count"] = intval($total);

        $lists = [];
        if ($res["total_count"]) {
            if ($pageSize > 0) {
                $criteria = $criteria->setFirstResult($pageSize * ($page - 1))
                    ->setMaxResults($pageSize);
            }
            if ($orderBy) {
                $criteria = $criteria->orderBy($orderBy);
            }
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }

        $res["list"] = $lists;
        return $res;
    }

    /**
     * 统计数量
     */
    public function countCopy($filter)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);

        return intval($total);
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["category_name"]) && $data["category_name"]) {
            $entity->setCategoryName($data["category_name"]);
        }
        if (isset($data["parent_id"])) {
            $entity->setParentId($data["parent_id"]);
        }
        if (isset($data["path"])) {
            $entity->setPath($data["path"]);
        }
        if (isset($data["sort"])) {
            $entity->setSort($data["sort"]);
        }
        if (isset($data["image_url"])) {
            $entity->setImageUrl($data["image_url"]);
        }
        if (isset($data["goods_params"])) {
            if (is_array($data["goods_params"])) {
                $entity->setGoodsParams(json_encode($data["goods_params"]));
            } else {
                $entity->setGoodsParams($data["goods_params"]);
            }
        }
        if (isset($data["goods_spec"])) {
            if (is_array($data["goods_spec"])) {
                $entity->setGoodsSpec(json_encode($data["goods_spec"]));
            } else {
                $entity->setGoodsSpec($data["goods_spec"]);
            }
        }
        if (isset($data["category_level"])) {
            $entity->setCategoryLevel($data["category_level"]);
        }
        if (isset($data["is_main_category"])) {
            $entity->setIsMainCategory($data["is_main_category"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        if (isset($data["distributor_id"])) {
            $entity->setDistributorId($data["distributor_id"]);
        }
        if (isset($data["crossborder_tax_rate"])) {
            $entity->setCrossborderTaxRate($data["crossborder_tax_rate"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        if (isset($data["category_code"]) && $data['category_code']) {
            $entity->setCategoryCode($data['category_code']);
        }

        if (isset($data["customize_page_id"]) && $data['customize_page_id']) {
            $entity->setCustomizePageId($data['customize_page_id']);
        }
        if (isset($data["category_id_taobao"]) && $data['category_id_taobao']) {
            $entity->setCategoryIdTaobao($data['category_id_taobao']);
        }
        if (isset($data["parent_id_taobao"]) && $data['parent_id_taobao']) {
            $entity->setParentIdTaobao($data['parent_id_taobao']);
        }
        if (isset($data["taobao_category_info"]) && $data['taobao_category_info']) {
            $entity->setTaobaoCategoryInfo($data['taobao_category_info']);
        }
        if (isset($data["invoice_tax_rate"]) ) {//&& $data['invoice_tax_rate']
            $entity->setInvoiceTaxRate($data['invoice_tax_rate']);
        }
        if (isset($data["invoice_tax_rate_id"]) ) {//&& $data['invoice_tax_rate_id']
            $entity->setInvoiceTaxRateId($data['invoice_tax_rate_id']);
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData($entity)
    {
        return [
            'id' => $entity->getCategoryId(),
            'category_id' => $entity->getCategoryId(),
            'company_id' => $entity->getCompanyId(),
            'category_name' => $entity->getCategoryName(),
            'label' => $entity->getCategoryName(),
            'parent_id' => $entity->getParentId(),
            'distributor_id' => $entity->getDistributorId(),
            'path' => $entity->getPath(),
            'sort' => $entity->getSort(),
            'is_main_category' => $entity->getIsMainCategory(),
            'goods_params' => $entity->getGoodsParams() ? json_decode($entity->getGoodsParams(), true) : [],
            'goods_spec' => $entity->getGoodsSpec() ? json_decode($entity->getGoodsSpec(), true) : [],
            'category_level' => $entity->getCategoryLevel(),
            'image_url' => $entity->getImageUrl(),
            'crossborder_tax_rate' => $entity->getCrossborderTaxRate(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'category_code' => $entity->getCategoryCode(),
            'customize_page_id' => $entity->getCustomizePageId(),
            'category_id_taobao' => $entity->getCategoryIdTaobao(),
            'parent_id_taobao' => $entity->getParentIdTaobao(),
            'taobao_category_info' => $entity->getTaobaoCategoryInfo() ? json_decode($entity->getTaobaoCategoryInfo(), true) : [],
            'invoice_tax_rate' => $entity->getInvoiceTaxRate(),
            'invoice_tax_rate_id' => $entity->getInvoiceTaxRateId(),
        ];
    }


    /**
     * 更新多条数数据
     *
     * @param array $filter 更新的条件
     * @param array $data 更新的内容
     * @param bool $needLiteral false表示不需要为值做双引号的操作
     * @return mixed
     */
    public function updateByFilter(array $filter, array $data, bool $needLiteral = true)
    {
        app('log')->info(__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));
        app('log')->info(__FUNCTION__.':'.__LINE__.':data:'.json_encode($data));
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->update($this->table);
        foreach ($data as $key => $val) {
            $qb = $qb->set($key, $needLiteral ? $qb->expr()->literal($val) : $val);
        }

        $qb = $this->_filter($filter, $qb);

        return $qb->set('updated', time())->execute();
    }
}
