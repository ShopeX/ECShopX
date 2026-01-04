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

namespace EspierBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use EspierBundle\Entities\ExportLog;

use Dingo\Api\Exception\ResourceException;

class ExportLogRepository extends EntityRepository
{
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        // EcShopX core
        $entity = new ExportLog();
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
    public function deleteBy($filter, $offset = 1, $limit = 100, $orderBy = [])
    {
        // EcShopX core
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
        if ($orderBy) {
            $criteria = $criteria->orderBy($orderBy);
        }
        if ($limit && $offset) {
            $criteria->setFirstResult($limit * ($offset - 1))
                ->setMaxResults($limit);
        }
        $entityList = $this->matching($criteria);

        if (!$entityList) {
            return true;
        }
        foreach ($entityList as $entity) {
            $filesystem = app('filesystem')->disk('import-file');
            $filesystem->delete('export/zip/'.$entity->getFileName());
            $em = $this->getEntityManager();
            $em->remove($entity);
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

        return $this->getColumnNamesData($entity);
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
            $criteria->setFirstResult($pageSize * ($page - 1))
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
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["log_id"]) && $data["log_id"]) {
            $entity->setLogId($data["log_id"]);
        }
        if (isset($data["company_id"])) {
            $entity->setCompanyId($data["company_id"]);
        }
        //当前字段非必填
        if (isset($data["file_name"]) && $data["file_name"]) {
            $entity->setFileName($data["file_name"]);
        }
        //当前字段非必填
        if (isset($data["file_url"]) && $data["file_url"]) {
            $entity->setFileUrl($data["file_url"]);
        }
        if (isset($data["export_type"]) && $data["export_type"]) {
            $entity->setExportType($data["export_type"]);
        }
        if (isset($data["handle_status"]) && $data["handle_status"]) {
            $entity->setHandleStatus($data["handle_status"]);
        }
        //当前字段非必填
        if (isset($data["error_msg"]) && $data["error_msg"]) {
            $entity->setErrorMsg($data["error_msg"]);
        }
        //当前字段非必填
        if (isset($data["finish_time"]) && $data["finish_time"]) {
            $entity->setFinishTime($data["finish_time"]);
        }
        //当前字段非必填
        if (isset($data["distributor_id"])) {
            $entity->setDistributorId(intval($data["distributor_id"]));
        }
        if (isset($data["operator_id"])) {
            $entity->setOperatorId(intval($data["operator_id"]));
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        if (isset($data["merchant_id"])) {
            $entity->setMerchantId(intval($data["merchant_id"]));
        }
        if (isset($data["supplier_id"])) {
            $entity->setSupplierId(intval($data["supplier_id"]));
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
        $result = [
            'log_id' => $entity->getLogId(),
            'company_id' => $entity->getCompanyId(),
            'file_name' => $entity->getFileName(),
            'file_url' => $entity->getFileUrl(),
            'export_type' => $entity->getExportType(),
            'handle_status' => $entity->getHandleStatus(),
            'error_msg' => $entity->getErrorMsg(),
            'finish_time' => $entity->getFinishTime(),
            'finish_date' => date('Y-m-d H:i:s', $entity->getFinishTime()),
            'distributor_id' => $entity->getDistributorId(),
            'operator_id' => $entity->getOperatorId(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'merchant_id' => $entity->getMerchantId(),
            'supplier_id' => $entity->getSupplierId(),
        ];
        return $result;
    }
}
