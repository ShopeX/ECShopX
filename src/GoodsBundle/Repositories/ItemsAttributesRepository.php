<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use GoodsBundle\Entities\ItemsAttributes;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangService;

class ItemsAttributesRepository extends EntityRepository
{
    use MagicLangTrait;

    private  $table = 'items_attributes';

    private $prk = 'attribute_id';
    private $multiLangField = [
        'attribute_name','attribute_memo'
    ];
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
//        $service = new MultiLangService();
//        $dataTmp = $service->getLangData($data,$this->multiLangField);
//        $data = $dataTmp['data'];
//        $langBag = $dataTmp['langBag'];
        $entity = new ItemsAttributes();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $dataRet = $this->getColumnNamesData($entity);
        $service = new MultiLangService();
        $service->addMultiLangByParams($dataRet['attribute_id'],$dataRet,$this->table);
        //$service->saveLang($dataRet['company_id'],$langBag,$this->table,$dataRet['attribute_id'],$this->table);
        return $dataRet;
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter array
     * @param $data array
     */
    public function updateOneBy(array $filter, array $data)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException("未查询到更新数据");
        }

        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        $service = new MultiLangService();
        $service->updateLangData($data,$this->table,$filter[$this->prk]);

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
            return true;
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
        // FIXME: check performance
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

        return $this->getColumnNamesData($entity);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function getInfo(array $filter)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }

        $info = $this->getColumnNamesData($entity);
        $service = new MultiLangService();
        $info = $service->getTranslationByLang($info,$info[$this->prk],$this->table);
        return $info;
    }

    /**
     * 统计数量
     */
    public function count($filter)
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
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $page = 1, $pageSize = 100, $orderBy = array())
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
            if ($orderBy) {
                $criteria = $criteria->orderBy($orderBy);
            }

            if ($pageSize > 0) {
                $criteria->setFirstResult($pageSize * ($page - 1))
                    ->setMaxResults($pageSize);
            }
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }

        $res["list"] = $lists;
        $service = new MultiLangService();
        $res["list"] = $service->getListAddLang($res["list"],$this->multiLangField,$this->table,$this->getLang(),$this->prk);
        return $res;
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
        if (isset($data["shop_id"])) {
            $entity->setShopId($data["shop_id"]);
        }
        if (isset($data["attribute_type"]) && $data["attribute_type"]) {
            $entity->setAttributeType($data["attribute_type"]);
        }
        if (isset($data["attribute_name"]) && $data["attribute_name"]) {
            $entity->setAttributeName($data["attribute_name"]);
        }
        //当前字段非必填
        if (isset($data["attribute_memo"])) {
            $entity->setAttributeMemo($data["attribute_memo"]);
        }
        if (isset($data["attribute_sort"])) {
            $entity->setAttributeSort($data["attribute_sort"]);
        }
        if (isset($data["distributor_id"])) {
            $entity->setDistributorId($data["distributor_id"]);
        }
        if (isset($data["is_show"])) {
            $entity->setIsShow($data["is_show"]);
        }
        if (isset($data["is_image"])) {
            $entity->setIsImage($data["is_image"]);
        }
        //当前字段非必填
        if (isset($data["image_url"])) {
            $entity->setImageUrl($data["image_url"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        if (isset($data["attribute_code"]) && $data["attribute_code"]) {
            $entity->setAttributeCode($data["attribute_code"]);
        }
        if (isset($data["attribute_show"]) && $data["attribute_show"]) {
            $entity->setAttributeShow($data["attribute_show"]);
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
            'attribute_id' => $entity->getAttributeId(),
            'company_id' => $entity->getCompanyId(),
            'shop_id' => $entity->getShopId(),
            'attribute_type' => $entity->getAttributeType(),
            'attribute_name' => $entity->getAttributeName(),
            'attribute_memo' => $entity->getAttributeMemo(),
            'attribute_sort' => $entity->getAttributeSort(),
            'distributor_id' => $entity->getDistributorId(),
            'is_show' => $entity->getIsShow(),
            'is_image' => $entity->getIsImage(),
            'image_url' => $entity->getImageUrl(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'attribute_code' => $entity->getAttributeCode(),
            'attribute_show' => $entity->getAttributeShow(),
        ];
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function getLists($filter, $page = 1, $pageSize = 100, $orderBy = array())
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
        $lists = [];
        if ($orderBy) {
            $criteria = $criteria->orderBy($orderBy);
        }

        if ($pageSize > 0) {
            $criteria->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
        }
        $entityList = $this->matching($criteria);
        foreach ($entityList as $entity) {
            $lists[] = $this->getColumnNamesData($entity);
        }
        $service = new MultiLangService();
        $lists = $service->getListAddLang($lists,$this->multiLangField,$this->table,$this->getLang(),$this->prk);

        return $lists;
    }
}
