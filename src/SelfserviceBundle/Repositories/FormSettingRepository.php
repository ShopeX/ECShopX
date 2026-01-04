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

namespace SelfserviceBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangOutsideItemService;
use SelfserviceBundle\Entities\FormSetting;

use Dingo\Api\Exception\ResourceException;
use Exception;


class FormSettingRepository extends EntityRepository
{
    use MagicLangTrait;
    var $table = 'selfservice_form_setting';

    public $prk = 'id';
    private $multiLangField = [
        'reason',
        'remark'
    ];
    public function getLangService()
    {
        return new MultiLangOutsideItemService($this->table,$this->table,$this->multiLangField);
    }
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new FormSetting();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $result =  $this->getColumnNamesData($entity);

        $this->getLangService()->addMultiLangByParams($result[$this->prk],$data,$this->table);

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
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException(trans("SelfserviceBundle.no_update_data_found"));
        }

        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        if(isset($filter[$this->prk])){
            $this->getLangService()->updateLangData($data,$this->table,$filter[$this->prk]);
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
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new ResourceException(trans("SelfserviceBundle.no_update_data_found"));
        }

        $em = $this->getEntityManager();
        $result = [];
        foreach ($entityList as $entityProp) {
            $entityProp = $this->setColumnNamesData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $tmp = $this->getColumnNamesData($entityProp);
            if(isset($tmp[$this->prk])){
                $this->getLangService()->updateLangData($data,$this->table,$tmp[$this->prk]);
            }
            $result[] = $tmp;
        }
        return $result;
    }

    public function discard($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            throw new ResourceException(trans("SelfserviceBundle.data_not_exist"));
        }
        $data['status'] = 2;
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        return true;
    }

    public function restore($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            throw new ResourceException(trans("SelfserviceBundle.data_not_exist"));
        }
        $data['status'] = 1;
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        return true;
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
            throw new Exception(trans("SelfserviceBundle.delete_data_not_exist"));
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
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new Exception(trans("SelfserviceBundle.delete_data_not_exist"));
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

        $result = $this->getColumnNamesData($entity);
        if ($result){
            $this->getLangService()->getOneLangData($result,$this->multiLangField,$this->table,$this->getLang(),$result[$this->prk],$this->table);
        }
        return $result;
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

        $result = $this->getColumnNamesData($entity);
        if ($result){
            $this->getLangService()->getOneLangData($result,$this->multiLangField,$this->table,$this->getLang(),$result[$this->prk],$this->table);
        }
        return $result;
    }

    /**
     * 统计数量
     */
    public function count($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(id)')
            ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    private function _filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
                    $value = '%'.$value.'%';
                }
                if (is_array($value)) {
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k($field, $value));
                } else {
                    $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                }
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in($field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
    }

    public function getLists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($filter, $qb);
        if ($orderBy) {
            foreach ($orderBy as $filed => $val) {
                $qb->addOrderBy($filed, $val);
            }
        }
        if ($pageSize > 0) {
            $qb->setFirstResult(($page - 1) * $pageSize)
                ->setMaxResults($pageSize);
        }
        $lists = $qb->execute()->fetchAll();
        $lists = $this->getLangService()->getListAddLang($lists,$this->multiLangField,$this->table,$this->getLang(),$this->prk);
        return $lists;
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $orderBy = ["id" => "DESC"], $pageSize = 100, $page = 1)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            if (!is_null($value)) {
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
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res["total_count"] = intval($total);

        $lists = [];
        if ($res["total_count"]) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }
        $res["list"] = $lists;
        $res["list"] = $this->getLangService()->getListAddLang($res["list"],$this->multiLangField,$this->table,$this->getLang(),$this->prk);
        return $res;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData(FormSetting $entity, $data)
    {
        if (isset($data["id"]) && $data["id"]) {
            $entity->setId($data["id"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["field_title"]) && $data["field_title"]) {
            $entity->setFieldTitle($data["field_title"]);
        }
        if (isset($data["distributor_id"])) {
            $entity->setDistributorId($data["distributor_id"]);
        }
        if (isset($data["field_name"]) && $data["field_name"]) {
            $entity->setFieldName($data["field_name"]);
        }
        if (isset($data["pic_name"]) && $data["pic_name"]) {
            $entity->setPicName($data["pic_name"]);
        }
        if (isset($data["form_element"]) && $data["form_element"]) {
            $entity->setFormElement($data["form_element"]);
        }
        if (isset($data["options"]) && $data["options"]) {
            $entity->setOptions(json_encode($data["options"]));
        }
        if (isset($data["image_url"]) && $data["image_url"]) {
            $entity->setImageUrl($data["image_url"]);
        }
        if (isset($data["status"]) && $data["status"]) {
            $entity->setStatus($data["status"]);
        }

        if (isset($data["sort"]) && $data["sort"]) {
            $entity->setSort($data["sort"]);
        }

        if (isset($data["is_required"])) {
            $entity->setIsRequired($data["is_required"]);
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData(FormSetting $entity)
    {
        return [
            'id' => $entity->getId(),
            'company_id' => $entity->getCompanyId(),
            'distributor_id' => $entity->getDistributorId(),
            'field_title' => $entity->getFieldTitle(),
            'field_name' => $entity->getFieldName(),
            'pic_name' => $entity->getPicName(),
            'form_element' => $entity->getFormElement(),
            'status' => $entity->getStatus(),
            'sort' => $entity->getSort(),
            'is_required' => $entity->getIsRequired(),
            'image_url' => $entity->getImageUrl(),
            'options' => json_decode($entity->getOptions()),
        ];
    }
}
