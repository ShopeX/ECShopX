<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use GoodsBundle\Entities\ItemsCategory;
use CompanysBundle\Ego\CompanysActivationEgo;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangService;
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
        }


        //获取语言
        $lang = $this->getLang();
        $result['list'] = (new MultiLangService())->getListAddLang($result['list'],$this->multiLangField,$this->table,$lang,'category_id');
        return $result;
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
