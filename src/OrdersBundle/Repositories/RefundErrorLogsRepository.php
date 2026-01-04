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

namespace OrdersBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use OrdersBundle\Entities\RefundErrorLogs;

use Dingo\Api\Exception\ResourceException;

class RefundErrorLogsRepository extends EntityRepository
{
    public $table = "refund_error_logs";
    public $cols = ['id','company_id','wxa_appid','data_json','status','error_code','error_desc','is_resubmit','create_time','update_time','merchant_id','distributor_id','supplier_id'];
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new RefundErrorLogs();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
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
            throw new ResourceException("未查询到更新数据");
        }

        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

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
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function _filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                    $value = '%'.$value.'%';
                }
                $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
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

    /**
     * 根据条件获取列表数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $page = 1, $pageSize = -1, $orderBy = array())
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
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }

        $res["list"] = $lists;
        return $res;
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

        return $this->getColumnNamesData($entity);
    }

    /**
     * 统计数量
     */
    public function count($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
             ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param RefundErrorLogs $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["id"]) && $data["id"]) {
            $entity->setId($data["id"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }

        if (isset($data["order_id"]) && $data["order_id"]) {
            $entity->setOrderId($data["order_id"]);
        }
        if (isset($data["supplier_id"])) {
            $entity->setSupplierId($data["supplier_id"]);
        }
        //当前字段非必填
        if (isset($data["wxa_appid"]) && $data["wxa_appid"]) {
            $entity->setWxaAppid($data["wxa_appid"]);
        }
        //当前字段非必填
        if (isset($data["data_json"]) && $data["data_json"]) {
            $entity->setDataJson($data["data_json"]);
        }
        //当前字段非必填
        if (isset($data["status"]) && $data["status"]) {
            $entity->setStatus($data["status"]);
        }
        //当前字段非必填
        if (isset($data["error_code"]) && $data["error_code"]) {
            $entity->setErrorCode($data["error_code"]);
        }
        //当前字段非必填
        if (isset($data["error_desc"]) && $data["error_desc"]) {
            $entity->setErrorDesc($data["error_desc"]);
        }
        //当前字段非必填
        if (isset($data["is_resubmit"])) {
            $entity->setIsResubmit($data["is_resubmit"]);
        }
        if (isset($data["create_time"]) && $data["create_time"]) {
            $entity->setCreateTime($data["create_time"]);
        }
        //当前字段非必填
        if (isset($data["update_time"]) && $data["update_time"]) {
            $entity->setUpdateTime($data["update_time"]);
        }
        if (isset($data['merchant_id'])) {
            $entity->setMerchantId($data['merchant_id']);
        }
        if (isset($data['distributor_id'])) {
            $entity->setDistributorId($data['distributor_id']);
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param RefundErrorLogs $entity
     */
    private function getColumnNamesData($entity)
    {
        return [
            'id' => $entity->getId(),
            'company_id' => $entity->getCompanyId(),
            'order_id' => $entity->getOrderId(),
            'supplier_id' => $entity->getSupplierId(),
            'wxa_appid' => $entity->getWxaAppid(),
            'data_json' => $entity->getDataJson(),
            'status' => $entity->getStatus(),
            'error_code' => $entity->getErrorCode(),
            'error_desc' => $entity->getErrorDesc(),
            'is_resubmit' => $entity->getIsResubmit(),
            'create_time' => $entity->getCreateTime(),
            'update_time' => $entity->getUpdateTime(),
            'merchant_id' => $entity->getMerchantId(),
            'distributor_id' => $entity->getDistributorId(),
        ];
    }
}
