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

namespace ReservationBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Dingo\Api\Exception\ResourceException;
use Doctrine\Common\Collections\Criteria;
use ReservationBundle\Entities\WorkShiftType;

class WorkShiftTypeRepository extends EntityRepository
{
    public $table = "reservation_shift_type";

    /*
     * create shift type (添加工作班次类型)
     *
     * @param paramsData array
     */
    public function createData($paramsData)
    {
        $shiftType = new WorkShiftType();
        $shiftType->setTypeName($paramsData['typeName']);
        $shiftType->setCompanyId($paramsData['companyId']);
        $shiftType->setBeginTime($paramsData['beginTime']);
        $shiftType->setEndTime($paramsData['endTime']);
        $shiftType->setStatus('valid');

        $em = $this->getEntityManager();
        $em->persist($shiftType);
        $em->flush();

        $result = [
            'type_id' => $shiftType->getTypeId(),
            'type_name' => $shiftType->getTypeName(),
            'company_id' => $shiftType->getCompanyId(),
            'begin_time' => $shiftType->getBeginTime(),
            'end_time' => $shiftType->getEndTime(),
        ];
        return $result;
    }

    /*
     * 删除班次类型,并一起清除已有的排班
     *
     * @param filter array
     */
    public function DeleteDataAndRel($filter, $shiftIds)
    {
        $conn = app('registry')->getConnection('default');
        $resourceLevel = $conn->fetchAssoc('select * from '.$this->table.' where type_id=? and company_id=?', [$filter['type_id'],$filter['company_id']]);
        if (!$resourceLevel) {
            return false;
        }

        $conn->beginTransaction();
        try {
            $conn->delete($this->table, $filter);
            foreach ($shiftIds as $id) {
                $relFiler = [
                'company_id' => $filter['company_id'],
                'shift_type_id' => $filter['type_id'],
                'id' => $id,
            ];
                $conn->delete('reservation_work_shift', $relFiler);
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }


    /*
     * 删除班次类型,假删除
     *
     * @param filter array
     * @param ifinvalid string bool 操作状态
     *
     * @return bool
     */
    public function deleteData($filter, $ifInvalid)
    {
        $typeData = $this->findOneBy($filter);
        if (!$typeData) {
            throw new ResourceException("排班类型删除失败");
        }
        $em = $this->getEntityManager();
        if ($ifInvalid == "invalid") {
            $typeData->setStatus('invalid');
            $em->persist($typeData);
        } elseif ($ifInvalid == "delete") {
            $em->remove($typeData);
        }
        $em->flush();
        return true;
    }

    public function updateData($filter, $paramsData)
    {
        $shiftType = $this->findOneBy($filter);
        $shiftType->setTypeName($paramsData['typeName']);
        $em = $this->getEntityManager();
        $em->persist($shiftType);
        $em->flush();

        $result = [
            'type_id' => $shiftType->getTypeId(),
            'type_name' => $shiftType->getTypeName(),
            'company_id' => $shiftType->getCompanyId(),
            'begin_time' => $shiftType->getBeginTime(),
            'end_time' => $shiftType->getEndTime(),
        ];
        return $result;
    }

    public function getData($typeId)
    {
        $data = [
            'typeName' => '休息',
            'beginTime' => '00:00',
            'endTime' => '23:59',
            'typeId' => '-1',
        ];
        if ($typeId != '-1') {
            $result = $this->find($typeId);
            if ($result) {
                $data = normalize($result);
            }
        }
        return $data;
    }

    /*
     * 获取班次类型列表
     *
     * @param filter array
     */
    public function getList($filter, $pageSize = 100, $page = 1, $orderBy = ['type_id' => 'DESC'])
    {
        $list = [];
        $criteria = $this->__filter($filter);
        $criteria = $criteria->orderBy($orderBy)
            ->setFirstResult($pageSize * ($page - 1))
            ->setMaxResults($pageSize);
        $resourceLevel = $this->matching($criteria);
        foreach ($resourceLevel as $level) {
            $data = normalize($level);
            $list[] = $data;
        }
        return $list;
    }

    public function getCount($filter)
    {
        $criteria = $this->__filter($filter);
        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        return intval($total);
    }

    private function __filter($filter)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            if (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }
        return $criteria;
    }
}
